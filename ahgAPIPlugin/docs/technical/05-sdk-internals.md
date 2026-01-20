# SDK Internals

This document covers the internal architecture and implementation details of the Python and JavaScript SDKs.

## Overview

Both SDKs follow the same architectural patterns:

1. **Resource-based design**: Each API resource has its own class
2. **Lazy initialization**: Resources created on first access
3. **Unified HTTP layer**: Single request method handles all API calls
4. **Consistent error mapping**: HTTP errors mapped to typed exceptions
5. **Built-in retry**: Automatic retry with exponential backoff
6. **Type safety**: Full type definitions for IDE support

---

## Python SDK Internals

### Project Structure

```
atom-ahg-python/
├── pyproject.toml          # Package configuration
├── src/atom_ahg/
│   ├── __init__.py         # Public exports
│   ├── client.py           # AtomClient class
│   ├── config.py           # Configuration handling
│   ├── exceptions.py       # Exception classes
│   ├── pagination.py       # Pagination utilities
│   ├── retry.py            # Retry logic
│   ├── types.py            # TypedDict definitions
│   └── resources/
│       ├── __init__.py
│       ├── base.py         # BaseResource
│       └── *.py            # Individual resources
├── tests/
│   ├── conftest.py
│   └── test_*.py
└── examples/
```

### AtomClient Class

```python
# client.py

from typing import Optional, Dict, Any
import httpx

class AtomClient:
    """Main client for AtoM AHG API."""

    def __init__(
        self,
        base_url: str,
        api_key: Optional[str] = None,
        bearer_token: Optional[str] = None,
        timeout: float = 30.0,
        culture: str = "en",
        max_retries: int = 3,
        retry_base_delay: float = 1.0,
        retry_max_delay: float = 60.0,
    ):
        self._base_url = base_url.rstrip("/")
        self._api_key = api_key
        self._bearer_token = bearer_token
        self._timeout = timeout
        self._culture = culture
        self._retry_config = RetryConfig(
            max_retries=max_retries,
            base_delay=retry_base_delay,
            max_delay=retry_max_delay,
        )

        # Lazy-loaded resources
        self._descriptions: Optional[DescriptionsResource] = None
        self._authorities: Optional[AuthoritiesResource] = None
        # ... other resources

        # HTTP client
        self._client = httpx.Client(
            timeout=timeout,
            headers=self._build_headers(),
        )

    def _build_headers(self) -> Dict[str, str]:
        headers = {
            "Accept": "application/json",
            "Content-Type": "application/json",
            "Accept-Language": self._culture,
        }
        if self._api_key:
            headers["X-API-Key"] = self._api_key
        elif self._bearer_token:
            headers["Authorization"] = f"Bearer {self._bearer_token}"
        return headers

    @property
    def descriptions(self) -> "DescriptionsResource":
        """Lazy-load descriptions resource."""
        if self._descriptions is None:
            self._descriptions = DescriptionsResource(self)
        return self._descriptions

    def request(
        self,
        method: str,
        path: str,
        params: Optional[Dict] = None,
        data: Optional[Dict] = None,
        **kwargs,
    ) -> Any:
        """Execute an API request with retry logic."""
        url = f"{self._base_url}/api/v2{path}"

        @retry_sync(self._retry_config)
        def do_request():
            response = self._client.request(
                method=method,
                url=url,
                params=params,
                json=data,
                **kwargs,
            )
            return self._handle_response(response)

        return do_request()

    def _handle_response(self, response: httpx.Response) -> Any:
        """Parse response and raise exceptions for errors."""
        try:
            data = response.json()
        except ValueError:
            data = {"message": response.text}

        if response.status_code >= 400:
            raise self._map_error(response.status_code, data)

        # Unwrap success response
        if isinstance(data, dict) and data.get("success"):
            return data.get("data", data)

        return data

    def _map_error(self, status_code: int, data: Dict) -> AtomAPIError:
        """Map HTTP status to appropriate exception."""
        error_classes = {
            400: AtomValidationError,
            401: AtomAuthenticationError,
            403: AtomForbiddenError,
            404: AtomNotFoundError,
            429: AtomRateLimitError,
        }

        if status_code >= 500:
            return AtomServerError(
                message=data.get("message", "Server error"),
                status_code=status_code,
                response_data=data,
            )

        error_class = error_classes.get(status_code, AtomAPIError)
        return error_class(
            message=data.get("message", "API error"),
            status_code=status_code,
            error_type=data.get("error"),
            response_data=data,
        )

    def close(self):
        """Close the HTTP client."""
        self._client.close()

    def __enter__(self):
        return self

    def __exit__(self, *args):
        self.close()
```

### BaseResource Class

```python
# resources/base.py

from typing import TypeVar, Generic, Dict, Any, Optional, Callable
from ..pagination import PaginatedResponse, Paginator, AsyncPaginator

T = TypeVar("T")

class BaseResource(Generic[T]):
    """Base class for all API resources."""

    def __init__(self, client: "AtomClient"):
        self._client = client

    def _request(
        self,
        method: str,
        path: str,
        params: Optional[Dict] = None,
        data: Optional[Dict] = None,
        **kwargs,
    ) -> Any:
        """Make an API request via the client."""
        return self._client.request(method, path, params, data, **kwargs)

    async def _request_async(
        self,
        method: str,
        path: str,
        params: Optional[Dict] = None,
        data: Optional[Dict] = None,
        **kwargs,
    ) -> Any:
        """Make an async API request via the client."""
        return await self._client.request_async(method, path, params, data, **kwargs)

    def _build_paginated_response(
        self,
        data: Dict,
        result_type: type = dict,
    ) -> PaginatedResponse[T]:
        """Build a PaginatedResponse from API data."""
        return PaginatedResponse(
            results=data.get("results", []),
            total=data.get("total", 0),
            limit=data.get("limit", 10),
            skip=data.get("skip", 0),
        )

    def _paginate(
        self,
        path: str,
        params: Optional[Dict] = None,
        page_size: int = 10,
    ) -> Paginator[T]:
        """Create a paginator for the given endpoint."""
        params = params or {}

        def fetch(limit: int, skip: int) -> PaginatedResponse[T]:
            params["limit"] = limit
            params["skip"] = skip
            data = self._request("GET", path, params=params)
            return self._build_paginated_response(data)

        return Paginator(fetch, page_size)
```

### Resource Implementation Example

```python
# resources/descriptions.py

from typing import Optional, Dict, List
from .base import BaseResource
from ..types import DescriptionSummary, DescriptionDetail, DescriptionCreate
from ..pagination import PaginatedResponse, Paginator

class DescriptionsResource(BaseResource[DescriptionSummary]):
    """Resource for archival descriptions."""

    def list(
        self,
        limit: int = 10,
        skip: int = 0,
        repository: Optional[str] = None,
        level: Optional[str] = None,
        sector: Optional[str] = None,
        sort: Optional[str] = None,
    ) -> PaginatedResponse[DescriptionSummary]:
        """List descriptions with filtering."""
        params = {
            "limit": limit,
            "skip": skip,
        }
        if repository:
            params["repository"] = repository
        if level:
            params["level"] = level
        if sector:
            params["sector"] = sector
        if sort:
            params["sort"] = sort

        data = self._request("GET", "/descriptions", params=params)
        return self._build_paginated_response(data)

    def get(self, slug: str, full: bool = True) -> DescriptionDetail:
        """Get a specific description by slug."""
        params = {"full": "1" if full else "0"}
        return self._request("GET", f"/descriptions/{slug}", params=params)

    def create(self, data: DescriptionCreate) -> DescriptionDetail:
        """Create a new description."""
        return self._request("POST", "/descriptions", data=dict(data))

    def update(self, slug: str, data: Dict) -> DescriptionDetail:
        """Update an existing description."""
        return self._request("PUT", f"/descriptions/{slug}", data=data)

    def delete(self, slug: str) -> None:
        """Delete a description."""
        self._request("DELETE", f"/descriptions/{slug}")

    def paginate(
        self,
        page_size: int = 10,
        repository: Optional[str] = None,
        level: Optional[str] = None,
        sector: Optional[str] = None,
    ) -> Paginator[DescriptionSummary]:
        """Iterate through all descriptions."""
        params = {}
        if repository:
            params["repository"] = repository
        if level:
            params["level"] = level
        if sector:
            params["sector"] = sector

        return self._paginate("/descriptions", params, page_size)
```

### Pagination Implementation

```python
# pagination.py

from typing import TypeVar, Generic, List, Iterator, Callable
from dataclasses import dataclass

T = TypeVar("T")

@dataclass
class PaginatedResponse(Generic[T]):
    """Response from a paginated API endpoint."""
    results: List[T]
    total: int
    limit: int
    skip: int

    @property
    def has_more(self) -> bool:
        """Check if more pages exist."""
        return self.skip + len(self.results) < self.total

    @property
    def page_number(self) -> int:
        """Current page number (1-indexed)."""
        return (self.skip // self.limit) + 1

    @property
    def total_pages(self) -> int:
        """Total number of pages."""
        if self.limit == 0:
            return 0
        return (self.total + self.limit - 1) // self.limit


class Paginator(Generic[T]):
    """Synchronous paginator for iterating through all pages."""

    def __init__(
        self,
        fetch_fn: Callable[[int, int], PaginatedResponse[T]],
        page_size: int = 10,
    ):
        self._fetch = fetch_fn
        self._page_size = page_size
        self._skip = 0
        self._done = False

    def __iter__(self) -> Iterator[PaginatedResponse[T]]:
        return self

    def __next__(self) -> PaginatedResponse[T]:
        if self._done:
            raise StopIteration

        result = self._fetch(self._page_size, self._skip)
        self._skip += self._page_size

        if not result.has_more:
            self._done = True

        return result


class AsyncPaginator(Generic[T]):
    """Asynchronous paginator for iterating through all pages."""

    def __init__(
        self,
        fetch_fn: Callable[[int, int], "Awaitable[PaginatedResponse[T]]"],
        page_size: int = 10,
    ):
        self._fetch = fetch_fn
        self._page_size = page_size
        self._skip = 0
        self._done = False

    def __aiter__(self):
        return self

    async def __anext__(self) -> PaginatedResponse[T]:
        if self._done:
            raise StopAsyncIteration

        result = await self._fetch(self._page_size, self._skip)
        self._skip += self._page_size

        if not result.has_more:
            self._done = True

        return result
```

### Retry Implementation

```python
# retry.py

import time
import random
from functools import wraps
from dataclasses import dataclass
from typing import Callable, TypeVar
from .exceptions import AtomRateLimitError, AtomServerError, AtomNetworkError

T = TypeVar("T")

@dataclass
class RetryConfig:
    max_retries: int = 3
    base_delay: float = 1.0
    max_delay: float = 60.0


def should_retry(error: Exception, attempt: int, config: RetryConfig) -> bool:
    """Determine if request should be retried."""
    if attempt >= config.max_retries:
        return False

    if isinstance(error, AtomRateLimitError):
        return True
    if isinstance(error, AtomServerError):
        return True
    if isinstance(error, AtomNetworkError):
        return True

    return False


def calculate_delay(
    attempt: int,
    config: RetryConfig,
    retry_after: float = None,
) -> float:
    """Calculate delay before next retry."""
    if retry_after:
        return retry_after

    # Exponential backoff
    delay = config.base_delay * (2 ** attempt)

    # Add jitter (0-25%)
    jitter = delay * random.random() * 0.25
    delay += jitter

    # Cap at max delay
    return min(delay, config.max_delay)


def retry_sync(config: RetryConfig):
    """Decorator for synchronous retry logic."""
    def decorator(func: Callable[..., T]) -> Callable[..., T]:
        @wraps(func)
        def wrapper(*args, **kwargs) -> T:
            last_error = None

            for attempt in range(config.max_retries + 1):
                try:
                    return func(*args, **kwargs)
                except Exception as e:
                    last_error = e

                    if not should_retry(e, attempt, config):
                        raise

                    retry_after = getattr(e, "retry_after", None)
                    delay = calculate_delay(attempt, config, retry_after)
                    time.sleep(delay)

            raise last_error

        return wrapper
    return decorator
```

### Type Definitions

```python
# types.py

from typing import TypedDict, Optional, List

class DescriptionSummary(TypedDict):
    """Summary view of a description."""
    slug: str
    title: str
    reference_code: Optional[str]
    level_of_description: str
    repository: Optional[str]
    date_range: Optional[str]
    updated_at: str


class DescriptionDetail(DescriptionSummary):
    """Full view of a description."""
    id: int
    scope_and_content: Optional[str]
    extent_and_medium: Optional[str]
    arrangement: Optional[str]
    dates: List[dict]
    access_points: List[dict]
    digital_objects: List[dict]
    parent: Optional[dict]
    children_count: int


class DescriptionCreate(TypedDict, total=False):
    """Data for creating a description."""
    title: str                        # Required
    level_of_description_id: int      # Required
    reference_code: str
    repository_id: int
    parent_id: int
    scope_and_content: str
    date_of_creation: str
```

---

## JavaScript SDK Internals

### Project Structure

```
atom-client-js/
├── package.json
├── tsconfig.json
├── src/
│   ├── index.ts            # Public exports
│   ├── client.ts           # AtomClient class
│   ├── config.ts           # Configuration
│   ├── errors.ts           # Exception classes
│   ├── pagination.ts       # Pagination utilities
│   ├── retry.ts            # Retry logic
│   ├── types/
│   │   └── index.ts        # Type definitions
│   └── resources/
│       ├── index.ts
│       ├── base.ts
│       └── *.ts
├── tests/
└── dist/                   # Built output
    ├── cjs/               # CommonJS
    ├── esm/               # ES Modules
    └── types/             # Type declarations
```

### AtomClient Class

```typescript
// client.ts

import { ClientConfig, RetryConfig, RequestOptions } from './types';
import { AtomAPIError, mapError } from './errors';
import { withRetry } from './retry';

const DEFAULT_CONFIG: Partial<ClientConfig> = {
  timeout: 30000,
  culture: 'en',
  retry: {
    maxRetries: 3,
    baseDelay: 1000,
    maxDelay: 60000,
  },
};

export class AtomClient {
  private readonly config: Required<ClientConfig>;
  private readonly apiBaseUrl: string;

  // Lazy-loaded resources
  private _descriptions?: DescriptionsResource;
  private _authorities?: AuthoritiesResource;
  // ... other resources

  constructor(config: ClientConfig) {
    this.config = {
      ...DEFAULT_CONFIG,
      ...config,
      retry: { ...DEFAULT_CONFIG.retry, ...config.retry },
    } as Required<ClientConfig>;

    this.apiBaseUrl = `${this.config.baseUrl.replace(/\/$/, '')}/api/v2`;
  }

  get descriptions(): DescriptionsResource {
    if (!this._descriptions) {
      this._descriptions = new DescriptionsResource(this);
    }
    return this._descriptions;
  }

  async request<T>(
    path: string,
    options: RequestOptions = {}
  ): Promise<T> {
    const url = `${this.apiBaseUrl}${path}`;
    const method = options.method || 'GET';

    const headers: Record<string, string> = {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'Accept-Language': this.config.culture,
      ...this.config.headers,
    };

    if (this.config.apiKey) {
      headers['X-API-Key'] = this.config.apiKey;
    } else if (this.config.bearerToken) {
      headers['Authorization'] = `Bearer ${this.config.bearerToken}`;
    }

    const fetchWithRetry = withRetry(
      async () => {
        const controller = new AbortController();
        const timeoutId = setTimeout(
          () => controller.abort(),
          this.config.timeout
        );

        try {
          const response = await fetch(url + this.buildQuery(options.params), {
            method,
            headers,
            body: options.body ? JSON.stringify(options.body) : undefined,
            signal: options.signal || controller.signal,
          });

          return this.handleResponse<T>(response);
        } finally {
          clearTimeout(timeoutId);
        }
      },
      this.config.retry
    );

    return fetchWithRetry();
  }

  private buildQuery(params?: Record<string, any>): string {
    if (!params || Object.keys(params).length === 0) {
      return '';
    }
    const searchParams = new URLSearchParams();
    for (const [key, value] of Object.entries(params)) {
      if (value !== undefined && value !== null) {
        searchParams.append(key, String(value));
      }
    }
    return `?${searchParams.toString()}`;
  }

  private async handleResponse<T>(response: Response): Promise<T> {
    let data: any;
    try {
      data = await response.json();
    } catch {
      data = { message: await response.text() };
    }

    if (!response.ok) {
      throw mapError(response.status, data, response.headers);
    }

    // Unwrap success response
    if (data?.success === true) {
      return data.data as T;
    }

    return data as T;
  }
}
```

### BaseResource Class

```typescript
// resources/base.ts

import { AtomClient, RequestOptions } from '../client';
import { PaginatedResponse, AsyncPaginator } from '../pagination';

export abstract class BaseResource<T> {
  constructor(protected readonly client: AtomClient) {}

  protected request<R>(
    path: string,
    options?: RequestOptions
  ): Promise<R> {
    return this.client.request<R>(path, options);
  }

  protected buildPaginatedResponse<R>(data: any): PaginatedResponse<R> {
    return new PaginatedResponse<R>(
      data.results || [],
      data.total || 0,
      data.limit || 10,
      data.skip || 0
    );
  }

  protected paginate<R>(
    path: string,
    params: Record<string, any> = {},
    pageSize: number = 10
  ): AsyncPaginator<R> {
    const fetchPage = async (
      limit: number,
      skip: number
    ): Promise<PaginatedResponse<R>> => {
      const data = await this.request<any>(path, {
        params: { ...params, limit, skip },
      });
      return this.buildPaginatedResponse<R>(data);
    };

    return new AsyncPaginator(fetchPage, pageSize);
  }
}
```

### Resource Implementation Example

```typescript
// resources/descriptions.ts

import { BaseResource } from './base';
import { PaginatedResponse, AsyncPaginator } from '../pagination';
import {
  DescriptionSummary,
  DescriptionDetail,
  DescriptionCreate,
  DescriptionUpdate,
} from '../types';

export interface ListDescriptionsOptions {
  limit?: number;
  skip?: number;
  repository?: string;
  level?: string;
  sector?: string;
  sort?: string;
}

export interface PaginateDescriptionsOptions {
  pageSize?: number;
  repository?: string;
  level?: string;
  sector?: string;
}

export class DescriptionsResource extends BaseResource<DescriptionSummary> {
  async list(
    options: ListDescriptionsOptions = {}
  ): Promise<PaginatedResponse<DescriptionSummary>> {
    const data = await this.request<any>('/descriptions', {
      params: options,
    });
    return this.buildPaginatedResponse(data);
  }

  async get(
    slug: string,
    options: { full?: boolean } = {}
  ): Promise<DescriptionDetail> {
    return this.request<DescriptionDetail>(`/descriptions/${slug}`, {
      params: { full: options.full !== false ? '1' : '0' },
    });
  }

  async create(data: DescriptionCreate): Promise<DescriptionDetail> {
    return this.request<DescriptionDetail>('/descriptions', {
      method: 'POST',
      body: data,
    });
  }

  async update(
    slug: string,
    data: DescriptionUpdate
  ): Promise<DescriptionDetail> {
    return this.request<DescriptionDetail>(`/descriptions/${slug}`, {
      method: 'PUT',
      body: data,
    });
  }

  async delete(slug: string): Promise<void> {
    await this.request(`/descriptions/${slug}`, {
      method: 'DELETE',
    });
  }

  paginate(
    options: PaginateDescriptionsOptions = {}
  ): AsyncPaginator<DescriptionSummary> {
    const { pageSize = 10, ...params } = options;
    return super.paginate('/descriptions', params, pageSize);
  }
}
```

### Pagination Implementation

```typescript
// pagination.ts

export class PaginatedResponse<T> {
  constructor(
    public readonly results: T[],
    public readonly total: number,
    public readonly limit: number,
    public readonly skip: number
  ) {}

  get hasMore(): boolean {
    return this.skip + this.results.length < this.total;
  }

  get pageNumber(): number {
    return Math.floor(this.skip / this.limit) + 1;
  }

  get totalPages(): number {
    return Math.ceil(this.total / this.limit);
  }
}

export class AsyncPaginator<T> {
  private skip = 0;
  private done = false;

  constructor(
    private readonly fetchPage: (
      limit: number,
      skip: number
    ) => Promise<PaginatedResponse<T>>,
    private readonly pageSize: number
  ) {}

  async *[Symbol.asyncIterator](): AsyncGenerator<PaginatedResponse<T>> {
    while (!this.done) {
      const page = await this.fetchPage(this.pageSize, this.skip);
      this.skip += this.pageSize;

      if (!page.hasMore) {
        this.done = true;
      }

      yield page;
    }
  }
}

export async function paginateAll<T>(
  paginator: AsyncPaginator<T>
): Promise<T[]> {
  const all: T[] = [];
  for await (const page of paginator) {
    all.push(...page.results);
  }
  return all;
}
```

### Error Implementation

```typescript
// errors.ts

export interface ErrorResponseData {
  error?: string;
  message?: string;
  errors?: Record<string, string[]>;
  request_id?: string;
}

export class AtomAPIError extends Error {
  constructor(
    message: string,
    public readonly statusCode?: number,
    public readonly errorType?: string,
    public readonly responseData: ErrorResponseData = {}
  ) {
    super(message);
    this.name = 'AtomAPIError';
  }

  get requestId(): string | undefined {
    return this.responseData.request_id;
  }
}

export class AtomValidationError extends AtomAPIError {
  constructor(
    message: string,
    statusCode: number,
    responseData: ErrorResponseData
  ) {
    super(message, statusCode, 'validation_error', responseData);
    this.name = 'AtomValidationError';
  }

  get errors(): Record<string, string[]> {
    return this.responseData.errors || {};
  }
}

export class AtomAuthenticationError extends AtomAPIError {
  constructor(message: string, responseData: ErrorResponseData) {
    super(message, 401, 'authentication_error', responseData);
    this.name = 'AtomAuthenticationError';
  }
}

export class AtomForbiddenError extends AtomAPIError {
  constructor(message: string, responseData: ErrorResponseData) {
    super(message, 403, 'forbidden', responseData);
    this.name = 'AtomForbiddenError';
  }
}

export class AtomNotFoundError extends AtomAPIError {
  constructor(message: string, responseData: ErrorResponseData) {
    super(message, 404, 'not_found', responseData);
    this.name = 'AtomNotFoundError';
  }
}

export class AtomRateLimitError extends AtomAPIError {
  public readonly retryAfter: number;

  constructor(
    message: string,
    retryAfter: number,
    responseData: ErrorResponseData
  ) {
    super(message, 429, 'rate_limit', responseData);
    this.name = 'AtomRateLimitError';
    this.retryAfter = retryAfter;
  }
}

export class AtomServerError extends AtomAPIError {
  constructor(
    message: string,
    statusCode: number,
    responseData: ErrorResponseData
  ) {
    super(message, statusCode, 'server_error', responseData);
    this.name = 'AtomServerError';
  }
}

export function mapError(
  statusCode: number,
  data: ErrorResponseData,
  headers: Headers
): AtomAPIError {
  const message = data.message || 'API Error';

  switch (statusCode) {
    case 400:
      return new AtomValidationError(message, statusCode, data);
    case 401:
      return new AtomAuthenticationError(message, data);
    case 403:
      return new AtomForbiddenError(message, data);
    case 404:
      return new AtomNotFoundError(message, data);
    case 429:
      const retryAfter = parseInt(headers.get('Retry-After') || '60', 10);
      return new AtomRateLimitError(message, retryAfter, data);
    default:
      if (statusCode >= 500) {
        return new AtomServerError(message, statusCode, data);
      }
      return new AtomAPIError(message, statusCode, data.error, data);
  }
}
```

### Retry Implementation

```typescript
// retry.ts

import { RetryConfig } from './types';
import { AtomRateLimitError, AtomServerError } from './errors';

const DEFAULT_RETRY_CONFIG: RetryConfig = {
  maxRetries: 3,
  baseDelay: 1000,
  maxDelay: 60000,
};

function shouldRetry(error: unknown, attempt: number, config: RetryConfig): boolean {
  if (attempt >= config.maxRetries) {
    return false;
  }

  if (error instanceof AtomRateLimitError) {
    return true;
  }
  if (error instanceof AtomServerError) {
    return true;
  }
  if (error instanceof Error && error.name === 'AbortError') {
    return false;
  }

  return false;
}

function calculateDelay(
  attempt: number,
  config: RetryConfig,
  retryAfter?: number
): number {
  if (retryAfter) {
    return retryAfter * 1000;
  }

  // Exponential backoff
  let delay = config.baseDelay * Math.pow(2, attempt);

  // Add jitter (0-25%)
  delay += delay * Math.random() * 0.25;

  // Cap at max delay
  return Math.min(delay, config.maxDelay);
}

function sleep(ms: number): Promise<void> {
  return new Promise(resolve => setTimeout(resolve, ms));
}

export function withRetry<T>(
  fn: () => Promise<T>,
  config: RetryConfig = DEFAULT_RETRY_CONFIG
): () => Promise<T> {
  return async () => {
    let lastError: unknown;

    for (let attempt = 0; attempt <= config.maxRetries; attempt++) {
      try {
        return await fn();
      } catch (error) {
        lastError = error;

        if (!shouldRetry(error, attempt, config)) {
          throw error;
        }

        const retryAfter =
          error instanceof AtomRateLimitError ? error.retryAfter : undefined;
        const delay = calculateDelay(attempt, config, retryAfter);

        await sleep(delay);
      }
    }

    throw lastError;
  };
}
```

---

## Build Configuration

### Python (pyproject.toml)

```toml
[build-system]
requires = ["hatchling"]
build-backend = "hatchling.build"

[project]
name = "atom-ahg"
version = "1.0.0"
description = "Python SDK for AtoM AHG Framework REST API v2"
requires-python = ">=3.8"
dependencies = [
    "httpx>=0.25.0",
]

[project.optional-dependencies]
dev = [
    "pytest>=7.0.0",
    "pytest-asyncio>=0.21.0",
    "pytest-httpx>=0.22.0",
]
```

### JavaScript (package.json)

```json
{
  "name": "@ahg/atom-client",
  "version": "1.0.0",
  "main": "dist/cjs/index.js",
  "module": "dist/esm/index.js",
  "types": "dist/types/index.d.ts",
  "exports": {
    ".": {
      "require": "./dist/cjs/index.js",
      "import": "./dist/esm/index.js",
      "types": "./dist/types/index.d.ts"
    }
  },
  "scripts": {
    "build": "npm run build:cjs && npm run build:esm && npm run build:types",
    "build:cjs": "tsc -p tsconfig.cjs.json",
    "build:esm": "tsc -p tsconfig.esm.json",
    "build:types": "tsc -p tsconfig.types.json"
  }
}
```

### TypeScript Configuration

```json
// tsconfig.json
{
  "compilerOptions": {
    "target": "ES2020",
    "module": "ESNext",
    "moduleResolution": "node",
    "strict": true,
    "declaration": true,
    "esModuleInterop": true,
    "skipLibCheck": true
  }
}

// tsconfig.cjs.json
{
  "extends": "./tsconfig.json",
  "compilerOptions": {
    "module": "CommonJS",
    "outDir": "./dist/cjs"
  }
}

// tsconfig.esm.json
{
  "extends": "./tsconfig.json",
  "compilerOptions": {
    "module": "ESNext",
    "outDir": "./dist/esm"
  }
}
```

---

## Testing

### Python Tests

```python
# tests/test_descriptions.py

import pytest
from atom_ahg import AtomClient, AtomNotFoundError

@pytest.fixture
def client(httpx_mock):
    return AtomClient(
        base_url="https://test.example.com",
        api_key="test-key"
    )

def test_list_descriptions(client, httpx_mock):
    httpx_mock.add_response(
        json={
            "success": True,
            "data": {
                "results": [{"slug": "test", "title": "Test"}],
                "total": 1,
                "limit": 10,
                "skip": 0
            }
        }
    )

    result = client.descriptions.list()

    assert len(result.results) == 1
    assert result.results[0]["slug"] == "test"
    assert result.total == 1

def test_get_not_found(client, httpx_mock):
    httpx_mock.add_response(
        status_code=404,
        json={
            "success": False,
            "error": "not_found",
            "message": "Not found"
        }
    )

    with pytest.raises(AtomNotFoundError):
        client.descriptions.get("nonexistent")
```

### JavaScript Tests

```typescript
// tests/descriptions.test.ts

import { AtomClient, AtomNotFoundError } from '../src';

describe('DescriptionsResource', () => {
  let client: AtomClient;

  beforeEach(() => {
    client = new AtomClient({
      baseUrl: 'https://test.example.com',
      apiKey: 'test-key',
    });

    global.fetch = jest.fn();
  });

  it('should list descriptions', async () => {
    (fetch as jest.Mock).mockResolvedValueOnce({
      ok: true,
      json: async () => ({
        success: true,
        data: {
          results: [{ slug: 'test', title: 'Test' }],
          total: 1,
          limit: 10,
          skip: 0,
        },
      }),
    });

    const result = await client.descriptions.list();

    expect(result.results).toHaveLength(1);
    expect(result.results[0].slug).toBe('test');
  });

  it('should throw NotFoundError for missing description', async () => {
    (fetch as jest.Mock).mockResolvedValueOnce({
      ok: false,
      status: 404,
      headers: new Headers(),
      json: async () => ({
        success: false,
        error: 'not_found',
        message: 'Not found',
      }),
    });

    await expect(client.descriptions.get('nonexistent')).rejects.toThrow(
      AtomNotFoundError
    );
  });
});
```
