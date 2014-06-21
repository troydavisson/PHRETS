<?php namespace PHRETS\Models\Metadata;

class Resource extends Base
{
    public $attributes = [
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

    public function getClasses()
    {
        return $this->getSession()->GetClassesMetadata($this->getResourceID());
    }
}
