<?php
declare (strict_types = 1);

namespace crud\generator\ui;

use crud\generator\ui\filter\DatetimeRange as FilterDatetimeRange;
use crud\generator\ui\filter\DateRange as FilterDateRange;
use crud\generator\ui\filter\EnumFilter;
use crud\generator\ui\filter\Input as FilterInput;
use crud\generator\ui\filter\Keyword as FilterKeyword;
use crud\generator\ui\filter\NumberRange as FilterNumberRange;
use crud\generator\ui\filter\RemoteMultiSelect as FilterRemoteMultiSelect;
use crud\generator\ui\filter\RemoteSelect as FilterRemoteSelect;
use crud\generator\ui\resolve\FilterKey;
use crud\generator\ui\resolve\FormKey;
use crud\generator\ui\resolve\ListKey;
use crud\generator\ui\resolve\SearchKey;
use crud\generator\ui\form\Color as FormColor;
use crud\generator\ui\form\Date as FormDate;
use crud\generator\ui\form\Datetime as FormDatetime;
use crud\generator\ui\form\ImageUpload as FormImageUpload;
use crud\generator\ui\form\Input as FormInput;
use crud\generator\ui\form\MultiSelect as FormMultiSelect;
use crud\generator\ui\form\Number as FormNumber;
use crud\generator\ui\form\Radio as FormRadio;
use crud\generator\ui\form\RemoteMultiSelect as FormRemoteMultiSelect;
use crud\generator\ui\form\RemoteSelect as FormRemoteSelect;
use crud\generator\ui\form\Select as FormSelect;
use crud\generator\ui\form\SwitchInput as FormSwitch;
use crud\generator\ui\form\Textarea as FormTextarea;
use crud\generator\ui\list\BelongsTo as ListBelongsTo;
use crud\generator\ui\list\BelongsToMany as ListBelongsToMany;
use crud\generator\ui\list\BoolCol as ListBool;
use crud\generator\ui\list\Color as ListColor;
use crud\generator\ui\list\Datetime as ListDatetime;
use crud\generator\ui\list\EnumTag as ListEnumTag;
use crud\generator\ui\list\Image as ListImage;
use crud\generator\ui\list\Number as ListNumber;
use crud\generator\ui\list\Tags as ListTags;
use crud\generator\ui\list\Text as ListText;
use crud\generator\ui\search\BoolSearch;
use crud\generator\ui\search\DateRange as SearchDateRange;
use crud\generator\ui\search\DatetimeRange as SearchDatetimeRange;
use crud\generator\ui\search\Input as SearchInput;
use crud\generator\ui\search\NumberRange as SearchNumberRange;
use crud\generator\ui\search\RemoteMultiSelect as SearchRemoteMultiSelect;
use crud\generator\ui\search\RemoteSelect as SearchRemoteSelect;
use crud\generator\ui\search\Select as SearchSelect;

/**
 * Registry 各模块规范 UI 键 → 渲染器（仅注册规范键）
 */
class Registry
{
    /** @var array<string, class-string<UiRenderer>> */
    private const array FORM = [
        'input' => FormInput::class,
        'textarea' => FormTextarea::class,
        'number' => FormNumber::class,
        'switch' => FormSwitch::class,
        'radio' => FormRadio::class,
        'select' => FormSelect::class,
        'multi_select' => FormMultiSelect::class,
        'color' => FormColor::class,
        'image_upload' => FormImageUpload::class,
        'date' => FormDate::class,
        'datetime' => FormDatetime::class,
        'remote_select' => FormRemoteSelect::class,
        'remote_multi_select' => FormRemoteMultiSelect::class,
    ];

    /** @var array<string, class-string<UiRenderer>> */
    private const array LIST = [
        'text' => ListText::class,
        'bool' => ListBool::class,
        'enum_tag' => ListEnumTag::class,
        'color' => ListColor::class,
        'image' => ListImage::class,
        'datetime' => ListDatetime::class,
        'tags' => ListTags::class,
        'number' => ListNumber::class,
        'belongs_to' => ListBelongsTo::class,
        'belongs_to_many' => ListBelongsToMany::class,
    ];

    /** @var array<string, class-string<UiRenderer>> */
    private const array SEARCH = [
        'input' => SearchInput::class,
        'bool' => BoolSearch::class,
        'select' => SearchSelect::class,
        'number_range' => SearchNumberRange::class,
        'date_range' => SearchDateRange::class,
        'datetime_range' => SearchDatetimeRange::class,
        'remote_select' => SearchRemoteSelect::class,
        'remote_multi_select' => SearchRemoteMultiSelect::class,
    ];

    /** @var array<string, class-string<FilterUiRenderer>> */
    private const array FILTER = [
        'keyword' => FilterKeyword::class,
        'input' => FilterInput::class,
        'number_range' => FilterNumberRange::class,
        'date_range' => FilterDateRange::class,
        'datetime_range' => FilterDatetimeRange::class,
        'remote_select' => FilterRemoteSelect::class,
        'remote_multi_select' => FilterRemoteMultiSelect::class,
        'enum' => EnumFilter::class,
    ];

    /** form 取表单渲染器；未知回退 input */
    public static function form(string $key): UiRenderer
    {
        $class = self::FORM[$key] ?? FormInput::class;

        return new $class();
    }

    /** list 取列表渲染器；未知回退 text */
    public static function list(string $key): UiRenderer
    {
        $class = self::LIST[$key] ?? ListText::class;

        return new $class();
    }

    /** search 取搜索渲染器；未知返回 null */
    public static function search(string $key): ?UiRenderer
    {
        if ($key === '' || !isset(self::SEARCH[$key])) {
            return null;
        }

        return new (self::SEARCH[$key])();
    }

    /** filter 取筛选渲染器；未知返回 null */
    public static function filter(string $key): ?FilterUiRenderer
    {
        if ($key === '' || !isset(self::FILTER[$key])) {
            return null;
        }

        return new (self::FILTER[$key])();
    }

    /** validateForm 校验表单控件参数 */
    public static function validateForm(array $field): void
    {
        $key = FormKey::resolve($field);
        $class = self::FORM[$key] ?? FormInput::class;
        $class::validate($field);
    }

    /** validateList 校验列表控件参数 */
    public static function validateList(array $field): void
    {
        $key = ListKey::resolve($field);
        $class = self::LIST[$key] ?? ListText::class;
        $class::validate($field);
    }

    /** validateSearch 校验搜索控件参数；无键则跳过 */
    public static function validateSearch(array $field): void
    {
        $key = SearchKey::resolve($field);
        if ($key === '' || !isset(self::SEARCH[$key])) {
            return;
        }
        self::SEARCH[$key]::validate($field);
    }

    /** validateFilter 校验筛选控件参数；无键则跳过 */
    public static function validateFilter(array $field): void
    {
        $key = FilterKey::resolve($field);
        if ($key === '' || !isset(self::FILTER[$key])) {
            return;
        }
        self::FILTER[$key]::validate($field);
    }
}
