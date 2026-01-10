<?php

class apiv2AuthoritiesReadAction extends AhgApiAction
{
    public function GET($request)
    {
        if (!$this->hasScope('read')) {
            return $this->error(403, 'Forbidden', 'Read scope required');
        }

        $slug = $request->getParameter('slug');
        $result = $this->repository->getAuthorityBySlug($slug);

        if (!$result) {
            return $this->error(404, 'Not Found', "Authority '$slug' not found");
        }

        return $this->success($result);
    }
}
