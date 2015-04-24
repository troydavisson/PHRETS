<?php namespace PHRETS\Models\Metadata;

/**
 * Class ResourceClass
 * @package PHRETS\Models\Metadata
 * @method string getClassName
 * @method string getVisibleName
 * @method string getStandardName
 * @method string getDescription
 * @method string getTableVersion
 * @method string getTableDate
 * @method string getUpdateVersion
 * @method string getUpdateDate
 * @method string getClassTimeStamp
 * @method string getDeletedFlagField
 * @method string getDeletedFlagValue
 * @method string getHasKeyIndex
 * @method string getVersion
 * @method string getDate
 * @method string getResource
 */
class ResourceClass extends Base
{
    protected $elements = [
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
    protected $attributes = [
        'Version',
        'Date',
        'Resource',
    ];

    /**
     * @return \Illuminate\Support\Collection|\PHRETS\Models\Metadata\Table[]
     */
    public function getTable()
    {
        return $this->getSession()->GetTableMetadata($this->getResource(), $this->getClassName());
    }
}
