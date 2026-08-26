<?php
declare (strict_types = 1);

namespace <%nsModel%>;

<%useBlock%>

/**
 * <%entity%> <%title%> Model（CRUD 生成）
 */
class <%entity%> extends Model
{
    /** @var string 数据表名称 */
    protected $name = '<%table%>';
<%connectionBlock%><%pkBlock%>
    /** @var list<string>|true|false */
    protected array|bool $autoWriteTimestamp = <%autoWriteTimestamp%>;

    /** @var array<string, string> */
    protected array $type = [
<%typeBlock%>
    ];
<%rels%><%helpers%>}
