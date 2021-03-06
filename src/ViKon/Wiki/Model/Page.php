<?php

namespace ViKon\Wiki\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use ViKon\Auth\Guard;

/**
 * \ViKon\Wiki\Model\Page
 *
 * @property integer                                                                       $id
 * @property string                                                                        $url
 * @property string                                                                        $type
 * @property string                                                                        $title
 * @property string                                                                        $toc
 * @property string                                                                        $content
 * @property boolean                                                                       $draft
 * @property-read \Illuminate\Database\Eloquent\Collection|\ViKon\Wiki\Model\PageContent[] $contents
 * @method static \Illuminate\Database\Query\Builder|\ViKon\Wiki\Model\Page whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\ViKon\Wiki\Model\Page whereUrl($value)
 * @method static \Illuminate\Database\Query\Builder|\ViKon\Wiki\Model\Page whereType($value)
 * @method static \Illuminate\Database\Query\Builder|\ViKon\Wiki\Model\Page whereTitle($value)
 * @method static \Illuminate\Database\Query\Builder|\ViKon\Wiki\Model\Page whereToc($value)
 * @method static \Illuminate\Database\Query\Builder|\ViKon\Wiki\Model\Page whereDraft($value)
 * @method static \Illuminate\Database\Query\Builder|\ViKon\Wiki\Model\Page whereContent($value)
 *
 * @author Kovács Vince<vincekovacs@hotmail.com>
 */
class Page extends Model
{
    use SoftDeletes;

    const FIELD_ID      = 'id';
    const FIELD_URL     = 'url';
    const FIELD_TYPE    = 'type';
    const FIELD_TITLE   = 'title';
    const FIELD_TOC     = 'toc';
    const FIELD_CONTENT = 'content';
    const FIELD_DRAFT   = 'draft';

    const TYPE_MARKDOWN = 'markdown';

    /**
     *
     * Disable updated_at and created_at columns
     *
     * @var boolean
     */
    public $timestamps = false;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table    = 'wiki_pages';

    protected $fillable = ['url', 'type'];

    public static function boot()
    {
        parent::boot();

        // Trigger delete all contents if page is deleted
        static::deleted(function (Page $page) {
            $page->contents()->delete();
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function contents()
    {
        return $this->hasMany(PageContent::class, 'page_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function refersTo()
    {
        return $this->belongsToMany(Page::class, 'wiki_pages_links', 'page_id', 'refers_to_page_id')
                    ->withPivot('url');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function refersFrom()
    {
        return $this->belongsToMany(Page::class, 'wiki_pages_links', 'refers_to_page_id', 'page_id');
    }

    /**
     * @return \ViKon\Wiki\Model\PageContent|null
     */
    public function userDraft()
    {
        return $this->contents()
                    ->where('draft', true)
                    ->where('created_by_user_id', app(Guard::class)->id())
                    ->orderBy('created_at', 'desc')
                    ->first();
    }

    /**
     * @return \ViKon\Wiki\Model\PageContent|null
     */
    public function lastContent()
    {
        return $this->contents()
                    ->where('draft', false)
                    ->orderBy('created_at', 'desc')
                    ->first();
    }

    /**
     * @param $toc
     *
     * @return mixed[]
     */
    public function getTocAttribute($toc)
    {
        return unserialize($toc);
    }

    /**
     * @param mixed $toc
     */
    public function setTocAttribute($toc)
    {
        $this->attributes['toc'] = serialize($toc);
    }
}