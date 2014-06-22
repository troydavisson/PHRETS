<?php namespace PHRETS\Models\Metadata;

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

    public function getTable()
    {
        return $this->getSession()->GetTableMetadata($this->getResource(), $this->getClass());
    }
}
