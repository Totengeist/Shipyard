<?php

namespace Shipyard;

use Shipyard\Models\Tag;

class ItemHelper {
    /**
     * Check and set flags for an item.
     *
     * @param string[] $data             the data submitted
     * @param bool     $anonymous_create whether this is an anonymous item creation
     *
     * @return int the flag bitfield
     */
    public static function get_flags($data, $anonymous_create = false) {
        $flags = 0;
        foreach ($data as $flag) {
            switch ($flag) {
                case 'private':
                    // Anonymized uploads cannot be marked private during creation. That's a mod-only action.
                    if (!$anonymous_create) {
                        $flags++;
                    }
                    break;
                case 'unlisted':
                    $flags += 2;
                    break;
                case 'locked':
                    $flags += 4;
                    break;
                default:
                    ; // noop to shut up SonarQube
            }
        }

        return $flags;
    }

    /**
     * Add and remove tags from a model.
     *
     * @param array<string, string> $data  the submitted data
     * @param Models\Item           $model the model to add and remove tags from
     *
     * @return void
     */
    public static function edit_tags($data, $model) {
        if (isset($data['remove_tags'])) {
            $tag_query = preg_replace('/[^0-9a-z-_,]/i', '', $data['remove_tags']);
            if ($tag_query !== null) {
                /** @var \Illuminate\Database\Eloquent\Builder $query */
                $query = Tag::query();
                $remove_tags = $query->whereIn('slug', explode(',', $tag_query))->get();
                $tag_ids = [];
                foreach ($remove_tags as $remove_tag) {
                    $tag_ids[] = $remove_tag->id;
                }
                $model->tags()->detach($tag_ids);
            }
        }
        if (isset($data['add_tags'])) {
            $tag_query = preg_replace('/[^0-9a-z-_,]/i', '', $data['add_tags']);
            if ($tag_query !== null) {
                /** @var \Illuminate\Database\Eloquent\Builder $query */
                $query = Tag::query();
                $add_tags = $query->whereIn('slug', explode(',', $tag_query))->get();
                $tag_ids = [];
                foreach ($add_tags as $add_tag) {
                    $tag_ids[] = $add_tag->id;
                }
                $model->tags()->attach($tag_ids, ['type' => get_class($model)::tag_label()]);
            }
        }
    }
}
