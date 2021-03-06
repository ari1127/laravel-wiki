<?php
/**
 * Created by PhpStorm.
 * User: van Gogh
 * Date: 2016. 01. 23.
 * Time: 19:26
 */

namespace ViKon\Wiki\Driver\Eloquent;

use Carbon\Carbon;
use ViKon\Auth\Guard;
use ViKon\Wiki\Contract\Page as PageContract;
use ViKon\Wiki\Model\Page as PageModel;
use ViKon\Wiki\Model\PageContent;

/**
 * Class Page
 *
 * @package ViKon\Wiki\Driver\Eloquent
 *
 * @author  Kovács Vince<vincekovacs@hotmail.com>
 */
class Page implements PageContract
{
    /** @type \ViKon\Wiki\Model\Page */
    protected $model;

    /** @type \ViKon\Wiki\Driver\Eloquent\Repository */
    protected $repository;

    /**
     * Page constructor.
     *
     * @param \ViKon\Wiki\Model\Page                 $page
     * @param \ViKon\Wiki\Driver\Eloquent\Repository $repository
     */
    public function __construct(PageModel $page, Repository $repository)
    {
        $this->model      = $page;
        $this->repository = $repository;
    }

    /**
     * {@inheritDoc}
     */
    public function getUrl()
    {
        return $this->model->url;
    }

    /**
     * {@inheritDoc}
     */
    public function setUrl($url)
    {
        $this->model->url = $url;
    }

    /**
     * {@inheritDoc}
     */
    public function getToken()
    {
        return $this->model->id;
    }

    /**
     * {@inheritDoc}
     */
    public function getTitle()
    {
        return $this->model->title;
    }

    /**
     * {@inheritDoc}
     */
    public function getContent()
    {
        return $this->model->content;
    }

    /**
     * {@inheritDoc}
     */
    public function isDraft()
    {
        return $this->model->draft;
    }

    /**
     * {@inheritDoc}
     */
    public function isPublished()
    {
        return !$this->isDraft();
    }

    /**
     * {@inheritDoc}
     */
    public function getContents()
    {
        return $this->model->contents->map(function (PageContent $pageContent) {
            return $this->repository->contentByModel($pageContent);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function getLastContent()
    {
        $guard = app(Guard::class);

        /** @type \ViKon\Wiki\Model\PageContent|null $pageContent */
        $pageContent = $this->model->contents()
                                   ->where(PageContent::FIELD_DRAFT, false)
                                   ->orderBy(PageContent::FIELD_CREATED_AT, 'desc')
                                   ->first();

        if ($pageContent === null) {
            $pageContent                     = new PageContent();
            $pageContent->draft              = true;
            $pageContent->created_by_user_id = $guard->id();
            $pageContent->created_at         = new Carbon();

            $this->model->contents()->save($pageContent);
        }

        return $this->repository->contentByModel($pageContent);
    }

    /**
     * {@inheritDoc}
     */
    public function getDraftForCurrentUser()
    {
        $guard = app(Guard::class);

        /** @type \ViKon\Wiki\Model\PageContent|null $pageContent */
        $pageContent = $this->model->contents()
                                   ->where(PageContent::FIELD_DRAFT, true)
                                   ->where(PageContent::FIELD_CREATED_BY_USER_ID, $guard->id())
                                   ->orderBy(PageContent::FIELD_CREATED_AT, 'desc')
                                   ->first();

        // If draft not found for current user than need create one
        if ($pageContent === null) {
            $lastContent = $this->getLastContent();

            $pageContent                     = new PageContent();
            $pageContent->title              = $lastContent->getTitle();
            $pageContent->content            = $lastContent->getRawContent();
            $pageContent->draft              = true;
            $pageContent->created_by_user_id = $guard->id();
            $pageContent->created_at         = new Carbon();

            $this->model->contents()->save($pageContent);
        }

        return $this->repository->contentByModel($pageContent);
    }

    /**
     * {@inheritDoc}
     */
    public function getToc()
    {
        return $this->model->toc;
    }

    /**
     * {@inheritDoc}
     */
    public function getHistory()
    {
        /** @type \Illuminate\Database\Eloquent\Collection $contents */
        $contents = $this->model->contents()
                                ->where('draft', false)
                                ->orderBy('created_at', 'desc')
                                ->get();

        return $contents->map(function (PageContent $pageContent) {
            return $this->repository->contentByModel($pageContent);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function getListOfPagesWithReferences()
    {
        // TODO implement list of pages with references
    }

    /**
     * {@inheritDoc}
     */
    public function getListOfReferredPages()
    {
        // TODO implement list of pages with references
    }

    /**
     * {@inheritDoc}
     */
    public function save()
    {
        $this->model->save();
    }

    /**
     * {@inheritDoc}
     */
    public function delete()
    {
        $this->model->delete();
    }

    /**
     * Get eloquent model which represents current page
     *
     * @return \ViKon\Wiki\Model\Page
     */
    public function getModel()
    {
        return $this->model;
    }
}