/**
 * Storage adapter for mirador-annotations that saves to ahgIiifPlugin's
 * /iiif/annotations endpoints (catalog #294).
 *
 * The page id is the same URL the manifest canvas links to, so Mirador keeps
 * one copy of the page whether it arrived through the manifest or through this
 * adapter. Only annotations stored in iiif_annotation (ids ending in
 * /iiif/annotations/<number>) can be changed; research notes shown on the same
 * page are read-only and are left alone.
 */
export default class AhgAnnotationAdapter {
  constructor(canvasId, baseUrl, objectId) {
    this.canvasId = canvasId;
    this.baseUrl = baseUrl.replace(/\/$/, '');
    this.objectId = objectId;
    this.annotationPageId = `${this.baseUrl}/iiif/annotations/object/${objectId}`;
  }

  static storedId(id) {
    const m = /\/iiif\/annotations\/(\d+)$/.exec(String(id || ''));
    return m ? m[1] : null;
  }

  async send(method, url, body) {
    const res = await fetch(url, {
      method,
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: body ? JSON.stringify(body) : undefined,
    });
    if (!res.ok) {
      throw new Error(`Annotation ${method} failed: ${res.status}`);
    }
  }

  async all() {
    const res = await fetch(this.annotationPageId, { credentials: 'same-origin' });
    if (!res.ok) {
      return { id: this.annotationPageId, type: 'AnnotationPage', items: [] };
    }
    const page = await res.json();
    // Only this canvas: the server page covers every canvas of the record.
    page.items = (page.items || []).filter((a) => {
      const source = a.target && (a.target.source || a.target);
      return !source || source === this.canvasId || (typeof source === 'object' && source.id === this.canvasId);
    });
    return page;
  }

  async get(annoId) {
    return (await this.all()).items.find((a) => a.id === annoId);
  }

  async create(annotation) {
    await this.send('POST', `${this.baseUrl}/iiif/annotations`, { ...annotation, object_id: this.objectId });
    return this.all();
  }

  async update(annotation) {
    const id = AhgAnnotationAdapter.storedId(annotation.id);
    if (id) {
      await this.send('PUT', `${this.baseUrl}/iiif/annotations/${id}`, annotation);
    }
    return this.all();
  }

  async delete(annoId) {
    const id = AhgAnnotationAdapter.storedId(annoId);
    if (id) {
      await this.send('DELETE', `${this.baseUrl}/iiif/annotations/${id}`);
    }
    return this.all();
  }
}
