<?php namespace PHRETS\Models\Metadata;

/**
 * Class Object
 * @package PHRETS\Models\Metadata
 * @method string getMetadataEntryID
 * @method string getVisibleName
 * @method string getObjectTimeStamp
 * @method string getObjectCount
 * @method string getObjectType
 * @method string getStandardName
 * @method string getMIMEType
 * @method string getDescription
 * @method string getVersion
 * @method string getDate
 * @method string getResource
 */
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
