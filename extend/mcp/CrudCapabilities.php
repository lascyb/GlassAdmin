<?php
declare (strict_types = 1);

namespace mcp;

use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;

/**
 * CrudCapabilities GlassAdmin Wave1 MCP 能力（CRUD 开发）
 */
class CrudCapabilities
{
    private CrudOps $ops;

    public function __construct(?CrudOps $ops = null)
    {
        $this->ops = $ops ?? new CrudOps();
    }

    /** crud_list_connections 列出可选数据库连接 */
    #[McpTool(name: 'crud_list_connections', description: '列出 config/database.php 中可选的数据库连接（不含账号密码）')]
    public function listConnections(): array
    {
        return $this->ops->listConnections();
    }

    /** crud_list_tables 列出可生成业务表 */
    #[McpTool(name: 'crud_list_tables', description: '列出指定连接可生成的业务表名（已过滤系统表；connection 省略为默认连接）')]
    public function listTables(string $connection = ''): array
    {
        return $this->ops->listTables($connection);
    }

    /** crud_get_table_schema 反推表结构为 CRUD 配置草稿 */
    #[McpTool(name: 'crud_get_table_schema', description: '按表名反推 CrudGenerateConfig 草稿（connection 省略为默认连接）')]
    public function getTableSchema(string $table, string $connection = ''): array
    {
        return $this->ops->getTableSchema($table, $connection);
    }

    /** crud_list_samples 列出 CRUD 样例 */
    #[McpTool(name: 'crud_list_samples', description: '列出 extend/crud/samples 下可用样例名')]
    public function listSamples(): array
    {
        return $this->ops->listSamples();
    }

    /** crud_get_sample 读取样例配置 */
    #[McpTool(name: 'crud_get_sample', description: '读取指定样例 JSON 配置')]
    public function getSample(string $name): array
    {
        return $this->ops->getSample($name);
    }

    /** crud_list_remote_controllers 远程下拉数据源控制器列表 */
    #[McpTool(name: 'crud_list_remote_controllers', description: '扫描 admin 控制器，返回远程 list 数据源元数据')]
    public function listRemoteControllers(): array
    {
        return $this->ops->listRemoteControllers();
    }

    /** crud_get_table_columns 表字段元数据 */
    #[McpTool(name: 'crud_get_table_columns', description: '按无前缀表名返回字段列表（含 pk；connection 省略为默认连接）')]
    public function getTableColumns(string $table, string $connection = ''): array
    {
        return $this->ops->getTableColumns($table, $connection);
    }

    /** crud_list_history 生成历史分页 */
    #[McpTool(name: 'crud_list_history', description: '分页列出 CRUD 生成历史')]
    public function listHistory(int $page = 1, int $limit = 10): array
    {
        return $this->ops->listHistory($page, $limit);
    }

    /** crud_get_history 生成历史详情 */
    #[McpTool(name: 'crud_get_history', description: '读取单条生成历史（含完整 config）')]
    public function getHistory(int $id): array
    {
        return $this->ops->getHistory($id);
    }

    /** crud_generate_dry_run 预览生成计划，不写盘不写库 */
    #[McpTool(name: 'crud_generate_dry_run', description: 'dry-run 预览将生成的文件/建表/权限，不落盘')]
    public function generateDryRun(
        #[Schema(type: 'object', additionalProperties: true, description: 'CrudGenerateConfig 对象')]
        array $config,
        bool $no_auth = false,
    ): array {
        return $this->ops->generate($config, dryRun: true, force: false, noAuth: $no_auth, saveHistory: false, confirm: false);
    }

    /**
     * crud_generate 真实执行 CRUD 生成
     *
     * 须 confirm=true；建议先调用 crud_generate_dry_run
     */
    #[McpTool(name: 'crud_generate', description: '真实执行 CRUD 生成（须 confirm=true；建议先 dry-run）')]
    public function generate(
        #[Schema(type: 'object', additionalProperties: true, description: 'CrudGenerateConfig 对象')]
        array $config,
        bool $confirm = false,
        bool $force = false,
        bool $no_auth = false,
        bool $save_history = true,
    ): array {
        return $this->ops->generate(
            $config,
            dryRun: false,
            force: $force,
            noAuth: $no_auth,
            saveHistory: $save_history,
            confirm: $confirm,
        );
    }

    /** glassadmin://crud/connections 可选数据库连接 */
    #[McpResource(uri: 'glassadmin://crud/connections', name: 'crud_connections', mimeType: 'application/json', description: '可选数据库连接列表')]
    public function resourceConnections(): array
    {
        return $this->ops->listConnections();
    }

    /** glassadmin://crud/tables 业务表名列表 */
    #[McpResource(uri: 'glassadmin://crud/tables', name: 'crud_tables', mimeType: 'application/json', description: '默认连接可生成业务表名列表')]
    public function resourceTables(): array
    {
        return $this->ops->listTables();
    }

    /** glassadmin://crud/samples 样例目录 */
    #[McpResource(uri: 'glassadmin://crud/samples', name: 'crud_samples', mimeType: 'application/json', description: 'CRUD 样例名列表')]
    public function resourceSamples(): array
    {
        return $this->ops->listSamples();
    }

    /** glassadmin://crud/history 生成历史列表 */
    #[McpResource(uri: 'glassadmin://crud/history', name: 'crud_history', mimeType: 'application/json', description: 'CRUD 生成历史（首页）')]
    public function resourceHistory(): array
    {
        return $this->ops->listHistory(1, 20);
    }

    /** glassadmin://crud/remote/controllers 远程数据源控制器 */
    #[McpResource(uri: 'glassadmin://crud/remote/controllers', name: 'crud_remote_controllers', mimeType: 'application/json', description: '远程 list 数据源控制器')]
    public function resourceRemoteControllers(): array
    {
        return $this->ops->listRemoteControllers();
    }

    /** glassadmin://crud/tables/{name}/schema 表结构草稿 */
    #[McpResourceTemplate(uriTemplate: 'glassadmin://crud/tables/{name}/schema', name: 'crud_table_schema', mimeType: 'application/json', description: '指定表的 CRUD 配置草稿')]
    public function resourceTableSchema(string $name): array
    {
        return $this->ops->getTableSchema($name);
    }

    /** glassadmin://crud/samples/{name} 单个样例 */
    #[McpResourceTemplate(uriTemplate: 'glassadmin://crud/samples/{name}', name: 'crud_sample', mimeType: 'application/json', description: '指定样例配置')]
    public function resourceSample(string $name): array
    {
        return $this->ops->getSample($name);
    }

    /** glassadmin://crud/history/{id} 单条历史 */
    #[McpResourceTemplate(uriTemplate: 'glassadmin://crud/history/{id}', name: 'crud_history_item', mimeType: 'application/json', description: '指定生成历史详情')]
    public function resourceHistoryItem(string $id): array
    {
        return $this->ops->getHistory((int) $id);
    }

    /** crud_from_table 根据表结构产出完整 generate config */
    #[McpPrompt(name: 'crud_from_table', description: '根据表名引导生成完整 CRUD generate config')]
    public function promptFromTable(string $table): array
    {
        return [
            [
                'role'    => 'user',
                'content' => "请先调用工具 crud_get_table_schema，表名为 `{$table}`，"
                    . '再基于返回草稿补全/修正 fields、menus、paths、remote 等配置，'
                    . '输出可直接传给 crud_generate_dry_run 的完整 config JSON。'
                    . '不要直接调用 crud_generate，除非用户明确要求写盘。',
            ],
        ];
    }

    /** crud_review_dry_run 审阅 dry-run 后再决定是否 generate */
    #[McpPrompt(name: 'crud_review_dry_run', description: '审阅 dry-run 结果，决定是否执行 crud_generate')]
    public function promptReviewDryRun(string $dry_run_json): array
    {
        return [
            [
                'role'    => 'user',
                'content' => "以下是 CRUD dry-run 结果，请审阅将写入的文件、建表与权限计划，"
                    . "指出风险（覆盖、系统路径、缺字段等）。若可执行，说明应使用的 confirm/force/no_auth 参数；"
                    . "不可执行则给出修改建议。\n\n```json\n{$dry_run_json}\n```",
            ],
        ];
    }
}
