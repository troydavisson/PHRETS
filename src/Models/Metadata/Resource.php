<?php namespace PHRETS\Models\Metadata;

class Resource extends Base
{
    protected $elements = [
        'ResourceID',
        'StandardName',
        'VisibleName',
        'Description',
        'KeyField',
        'ClassCount',
        'ClassVersion',
        'ClassDate',
        'ObjectVersion',
        'ObjectDate',
        'SearchHelpVersion',
        'SearchHelpDate',
        'EditMaskVersion',
        'EditMaskDate',
        'LookupVersion',
        'LookupDate',
        'UpdateHelpVersion',
        'UpdateHelpDate',
        'ValidationExpressionVersion',
        'ValidationExpressionDate',
        'ValidationLookupVersion',
        'ValidationLookupDate',
        'ValidationExternalVersion',
        'ValidationExternalDate',
    ];
    protected $attributes = [
        'Version',
        'Date',
    ];

    public function getClasses()
    {
        return $this->getSession()->GetClassesMetadata($this->getResourceID());
    }

    public function getObject()
    {
        return $this->getSession()->GetObjectMetadata($this->getResourceID());
    }
}
