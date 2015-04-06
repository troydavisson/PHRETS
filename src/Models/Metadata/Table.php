<?php namespace PHRETS\Models\Metadata;

/**
 * Class Table
 * @package PHRETS\Models\Metadata
 * @method string getSystemName
 * @method string getStandardName
 * @method string getLongName
 * @method string getDBName
 * @method string getShortName
 * @method string getMaximumLength
 * @method string getDataType
 * @method string getPrecision
 * @method string getSearchable
 * @method string getInterpretation
 * @method string getAlignment
 * @method string getUseSeparator
 * @method string getEditMaskID
 * @method string getLookupName
 * @method string getMaxSelect
 * @method string getUnits
 * @method string getIndex
 * @method string getMinimum
 * @method string getMaximum
 * @method string getDefault
 * @method string getRequired
 * @method string getSearchHelpID
 * @method string getUnique
 * @method string getMetadataEntryID
 * @method string getModTimeStamp
 * @method string getForeignKeyName
 * @method string getForeignField
 * @method string getInKeyIndex
 * @method string getVersion
 * @method string getDate
 * @method string getResource
 * @method string getClass
 */
class Table extends Base
{
    protected $elements = [
        'SystemName',
        'StandardName',
        'LongName',
        'DBName',
        'ShortName',
        'MaximumLength',
        'DataType',
        'Precision',
        'Searchable',
        'Interpretation',
        'Alignment',
        'UseSeparator',
        'EditMaskID',
        'LookupName',
        'MaxSelect',
        'Units',
        'Index',
        'Minimum',
        'Maximum',
        'Default',
        'Required',
        'SearchHelpID',
        'Unique',
        'MetadataEntryID',
        'ModTimeStamp',
        'ForeignKeyName',
        'ForeignField',
        'InKeyIndex',
    ];
    protected $attributes = [
        'Version',
        'Date',
        'Resource',
        'Class',
    ];

    /**
     * @return \Illuminate\Support\Collection|\PHRETS\Models\Metadata\LookupType[]
     */
    public function getLookupValues()
    {
        return $this->session->GetLookupValues($this->getResource(), $this->getLookupName());
    }
}
