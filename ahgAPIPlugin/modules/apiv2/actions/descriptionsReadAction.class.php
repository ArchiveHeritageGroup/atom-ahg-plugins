<?php

class apiv2DescriptionsReadAction extends AhgApiAction
{
    public function GET($request)
    {
        if (!$this->hasScope('read')) {
            return $this->error(403, 'Forbidden', 'Read scope required');
        }

        $slug = $request->getParameter('slug');
        if (empty($slug)) {
            return $this->error(400, 'Bad Request', 'Slug parameter required');
        }

        $full = filter_var($request->getParameter('full', false), FILTER_VALIDATE_BOOLEAN);

        if ($full) {
            $result = $this->repository->getFullDescription($slug);
        } else {
            $result = $this->repository->getDescriptionBySlug($slug);
        }

        if (!$result) {
            return $this->error(404, 'Not Found', "Description '{$slug}' not found");
        }

        return $this->success($result);
    }
}
