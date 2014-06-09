<?php namespace PHRETS\Models\Metadata;

class ResourceClass extends Base
{
    public $attributes = [
            'ClassName',
            'VisibleName',
            'StandardName',
            'Description',
            'TableVersion',
            'TableDate',
            'UpdateVersion',
            'UpdateDate',
            'ClassTimeStamp',
            'DeletedFlagField',
            'DeletedFlagValue',
            'HasKeyIndex',

    ];
}
