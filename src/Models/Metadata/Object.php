<?php namespace PHRETS\Models\Metadata;

class Object extends Base
{
    protected $elements = [
        'MetadataEntryID',
        'VisibleName',
        'ObjectTimeStamp',
        'ObjectCount',
        'ObjectType',
        'StandardName',
        'MIMEType',
        'Description',
    ];
    protected $attributes = [
        'Version',
        'Date',
        'Resource',
    ];
}
