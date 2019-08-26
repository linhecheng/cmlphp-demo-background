<?php
/* * *********************************************************
* 根据model/table自动生成增删改查
* @Author  linhecheng<linhechengbush@live.com>
* @Date: 2019/3/19 20:43
* *********************************************************** */

namespace adminbase\Service\Builder\Auto;

use adminbase\Controller\CommonController;
use adminbase\Service\AclService;
use adminbase\Service\Builder\Comm\InputType;
use adminbase\Service\Builder\FormBuildService;
use adminbase\Service\Builder\Grid\Button\Add;
use adminbase\Service\Builder\Grid\Button\Del;
use adminbase\Service\Builder\Grid\Button\Edit;
use adminbase\Service\Builder\Grid\Button\Export;
use adminbase\Service\Builder\Grid\Button\Normal;
use adminbase\Service\Builder\Grid\Column\Checkbox;
use adminbase\Service\Builder\Grid\Column\ColumnType;
use adminbase\Service\Builder\GridBuildService;
use adminbase\Service\System\LogService;
use Cml\Config;
use function Cml\dd;
use Cml\Http\Input;
use Cml\Model;
use Cml\Route;
use Cml\Vendor\Acl;
use Cml\View;

class AutoAdminController extends CommonController
{
    /**
     * model的名称 可用xxxModel::class
     *
     * @Optional 可选配置
     *
     * @var string
     */
    protected $model = '';

    /**
     * 不带前缀的表名, 跟model二选一。model优先
     *
     * @Optional 可选配置
     *
     * @var string
     */
    protected $table = '';

    /**
     * 只显示的字段 不传显示所有
     *
     * @Optional 可选配置
     *
     * @var array
     */
    protected $showFields = [];

    /**
     * 排除的字段 不传显示所有
     *
     * @Optional 可选配置
     *
     * @var array
     */
    protected $hideFields = [];

    /**
     * 字段映射成中文显示 不配置会取数据表注释-无注释直接显示字段名
     *
     * @Optional 可选配置
     *
     * @var array
     */
    protected $fieldLabel = [];

    /**
     * 配置字段的类型 字段 => 类型
     *
     * 字段 => adminbase\Service\Builder\Grid\Column\ColumnType 默认为文本类型
     * 主要是列表页用于按类型展示 添加/编辑数据的时候会自动识别表字段 datetime  date  text blob类型，展示日期/textarea表单项
     * 比较特殊的是如果配置成 ColumnType::Image 那么列表页显示为图片，添加/编辑页显示为上传图片按钮
     * 如果配置成 ColumnType::Link 那么列表页显示为附件下载超链接，添加/编辑页显示为上传文件按钮
     * 其它情况下添加/编辑会忽略本配置，自动获取数据库表字段类型按 datetime  date  text blob 展示日期/textarea表单项
     *
     * @Optional 可选配置
     *
     * @var array
     */
    protected $fieldType = [];

    /**
     * 当前控制器路径
     *
     * @Optional 可选配置,不传会自动获取当前路由的基地址
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * 页面的标题
     *
     * @Optional 可选配置
     *
     * @var string
     */
    protected $pageTitle = '数据列表';

    /**
     * 增删改的按钮是否显示
     *
     * 要显示的操作按钮 => [限定特定用户组才显示/空则都显示]
     *
     * @var array
     */
    protected $aclAction = [
        self::ACTION_ACL_ADD => [],
        self::ACTION_ACL_EDIT => [],
        self::ACTION_ACL_DEL => []
    ];

    /**
     * 添加操作的权限权限标志位
     */
    const ACTION_ACL_ADD = 1;

    /**
     * 编辑操作的权限标志位
     */
    const ACTION_ACL_EDIT = 2;

    /**
     * 删除操作的权限标志位
     */
    const ACTION_ACL_DEL = 3;

    /**
     * 根据model或table实例化后的db实例
     *
     * @var Model
     */
    private $modelInst = null;

    /**
     * 主键
     *
     * @var string
     */
    private $pkFieldName = '';

    /**
     * sql条件映射
     *
     * @var array
     */
    private $optMap = [
        '=' => [
            'op' => 'where'
            , 'placeholder' => ''
        ],
        '!=' => [
            'op' => 'whereNot'
            , 'placeholder' => ''
        ],
        '>' => [
            'op' => 'whereGt'
            , 'placeholder' => ''
        ],
        '<' => [
            'op' => 'whereLt'
            , 'placeholder' => ''
        ],
        '>=' => [
            'op' => 'whereGte'
            , 'placeholder' => ''
        ],
        '<=' => [
            'op' => 'whereLte'
            , 'placeholder' => ''
        ],
        'Like' => [
            'op' => 'whereLike'
            , 'placeholder' => 'xxx% 或 %xxx%'
        ],
        'Not Like' => [
            'op' => 'whereNotLike'
            , 'placeholder' => 'xxx% 或 %xxx%'
        ],
        'In' => [
            'op' => 'whereIn'
            , 'placeholder' => '1,2,3'
        ],
        'Not In' => [
            'op' => 'whereNotIn'
            , 'placeholder' => '1,2,3'
        ],
        'Is Null' => [
            'op' => 'whereNull'
        ],
        'Not Null' => [
            'op' => 'whereNull'
        ],
        'Between' => [
            'op' => 'whereBetween'
            , 'placeholder' => '1,2'
        ],
        'Not Between' => [
            'op' => 'whereNotBetween'
            , 'placeholder' => '1,2'
        ]
    ];

    /**
     * 逻辑操作
     *
     * @var array
     */
    private $logicalMap = [
        'AND' => '_and',
        'OR' => '_or'
    ];

    public function init()
    {
        Config::set('html_theme', 'kit_admin');
        parent::init();
        if (Acl::isSuperUser() && !$this->model && !$this->table) {
            $cacheKey = 'auto-table-uid-' . Acl::$authUser['id'];

            $this->table = Input::getString('auto_table', null);
            $this->table ? Model::staticCache()->set('auto-table-uid-' . Acl::$authUser['id'], $this->table, 86400) : $this->table = Model::staticCache()->get($cacheKey);
            in_array(Config::get('default_db.master.tableprefix') . $this->table, Model::getInstance()->getTables()) || exit('not found');

        }

        $this->baseUrl || $this->baseUrl = implode('/', array_slice(Route::getPathInfo(), 0, count(Route::getPathInfo()) - 1)) . '/';
        $this->modelInst = $this->model ? call_user_func($this->model . '::getInstance') : Model::getInstance($this->table);
        $this->pkFieldName = $this->modelInst->getPk($this->modelInst->getTableName(true), '');
    }

    /**
     * 主页面
     *
     */
    public function index()
    {
        $query = GridBuildService::create($this->baseUrl . 'ajaxPage', $this->pageTitle);
        $this->hadActionAcl(self::ACTION_ACL_DEL) && $query->addColumn(function () {
            $checkBoxColumn = new Checkbox();
            $checkBoxColumn->setDataColumnFieldName('id')
                ->addButton(function () {
                    $delButton = new Del();
                    $delButton->setText('删除')
                        ->setUrl($this->baseUrl . 'del')
                        ->setClass('layui-btn-danger');
                    return $delButton;
                });
            return $checkBoxColumn;
        });

        $query->addTopButton(function () {
            $filter = new Normal();
            $filter->setText('筛选')
                ->setClass('layui-btn-primary grid-filter');
            return $filter;
        })->addTopButton(function () {
            $export = new Export();
            $export->setText('导出')
                ->setUrl($this->baseUrl . 'export');
            return $export;
        });
        $this->hadActionAcl(self::ACTION_ACL_ADD) && $query->addTopButton(function () {
            $addBtn = new Add();
            $addBtn->setText('新增')
                ->setNewPageTitle('新增')
                ->setClass('layui-btn-normal')
                ->setNewPageUrl($this->baseUrl . 'add')
                ->setSaveDataUrl($this->baseUrl . 'save');
            return $addBtn;
        });

        $fields = $this->getFields();

        foreach ($fields as $field) {
            $query = $query->addColumn($field['field__label'], $field['Field'], '', $field['field__type']);
        }

        if ($this->hadActionAcl(self::ACTION_ACL_EDIT) || $this->hadActionAcl(self::ACTION_ACL_DEL)) {
            $query->addColumn(function () {
                $buttons = [];
                if ($this->hadActionAcl(self::ACTION_ACL_EDIT)) {
                    $buttonEdit = new Edit();
                    $buttonEdit->setText('编辑')
                        ->setNewPageTitle('编辑')
                        ->setNewPageUrl($this->baseUrl . 'edit')
                        ->setSaveDataUrl($this->baseUrl . 'save');
                    $buttons[] = $buttonEdit;
                }

                if ($this->hadActionAcl(self::ACTION_ACL_DEL)) {
                    $buttonDel = new Del();
                    $buttonDel->setText('删除')
                        ->setUrl($this->baseUrl . 'del')
                        ->setClass('layui-btn-danger');
                    $buttons[] = $buttonDel;
                }

                $buttonColumn = new \adminbase\Service\Builder\Grid\Column\Button();
                $buttonColumn->setText('操作')
                    ->setDataColumnFieldName($this->modelInst->db()->getPk($this->modelInst->getTableName()))
                    ->addButtons($buttons);
                return $buttonColumn;
            });
        }

        $query->setLayerWidthHeight("['60%', '90%']")
            ->appendFooter($this->search())
            ->display();
    }

    /**
     * 分页获取
     *
     * @acljump ./index
     */
    public function ajaxPage($parase = '')
    {
        $fields = Input::requestString('field');
        $operator = $_REQUEST['where'];
        $value = Input::requestString('value');
        $logical = Input::requestString('logical');

        $dbFields = [];
        foreach ($this->getFields() as $field) {
            $dbFields[$field['Field']] = $field['Type'];
        }

        if ($fields) {
            $i = 0;
            foreach ($fields as $key => $field) {
                if (!isset($operator[$key]) || !isset($this->optMap[$operator[$key]]) || !isset($value[$key]) || !isset($dbFields[$field]) /*|| !isset($this->logicalMap[$logical[$key]])*/) {
                    continue;
                }
                if ($i > 0 && !isset($this->logicalMap[$logical[$key]])) {
                    continue;
                }
                if (mb_stripos($dbFields[$field], 'int') !== false) {
                    if ($value[$key] === '') continue;
                    $value[$key] = intval($value[$key]);
                }

                $currentValue = $value[$key];

                $i > 0 && call_user_func_array([$this->modelInst, $this->logicalMap[$logical[$key]]], []);
                $query = [$field, $currentValue];

                $isIn = false;
                switch ($operator[$key]) {
                    case 'Like':
                    case 'Not Like':
                        $query = [
                            $field,
                            mb_substr($currentValue, 0, 1) === '%',
                            trim($currentValue, '%'),
                            mb_substr($currentValue, -1) === '%'
                        ];
                        break;
                    case 'In':
                    case 'Not In':
                        $isIn = true;
                    case 'Between':
                    case 'Not Between':
                        $currentValue = array_filter(explode(',', trim($currentValue)), function ($item) {
                            return !empty($item);
                        });
                        if (empty($currentValue) || (!$isIn && count($currentValue) < 2)) {
                            continue 2;
                        }
                        $query = $isIn ? [
                            $field,
                            $currentValue
                        ] : [
                            $field,
                            $currentValue[0],
                            $currentValue[1],
                        ];
                        break;
                }

                call_user_func_array([$this->modelInst, $this->optMap[$operator[$key]]['op']], $query);

                $i++;
            }
        }
        $dbFields = array_keys($dbFields);
        $dbFields = array_map(function ($item) {
            return "`$item`";
        }, $dbFields);

        $totalCount = $this->modelInst->paramsAutoReset(false, true)->getTotalNums();

        if ($parase == 'exportss') {
            return $this->modelInst->columns($dbFields)->orderBy('id', 'desc')->getList();
        } else {
            $list = $this->modelInst->columns($dbFields)->getListByPaginate(Config::get("page_num"));

            $this->renderJson(0, ['list' => $list, 'totalCount' => $totalCount]);
        }
    }

    /**
     * 添加
     *
     * @acljump ./index
     * @param array $data 有值为编辑，空为添加
     */
    public function add($data = [])
    {
        $this->hadActionAcl(self::ACTION_ACL_ADD) || AclService::noPermission();

        $query = FormBuildService::create($data ? '修改信息' : '新增记录');
        $fields = $this->getFields(false);

        foreach ($fields as $field) {
            $isAutoIncrement = $field['field__auto_inc'];
            $query->addFormItem($field['field__label'] . ($isAutoIncrement ? "(只读)" : ''), $field['Field'], '', '', $isAutoIncrement ? 'readonly' : '', $field['field__type'], 'adminbase/Public/upload');
            if (!$data[$field['Field']]) {
                switch ($field['field__type']) {
                    case InputType::Date:
                        $data[$field['Field']] = date('Y-m-d');
                        break;
                    case InputType::DateTime:
                        $data[$field['Field']] = date('Y-m-d H:i:s');
                        break;
                }
            }
        }
        $query->withData($data)->display();
    }

    /**
     * 编辑
     *
     * @acljump ./index
     */
    public function edit()
    {
        $this->hadActionAcl(self::ACTION_ACL_EDIT) || AclService::noPermission();

        $id = Input::getString($this->pkFieldName);
        $this->add($this->modelInst->getByColumn($id));
    }

    /**
     * 保存
     *
     * @acljump ./index
     */
    public function save()
    {
        $pk = Input::requestString($this->pkFieldName);

        $this->hadActionAcl($pk ? self::ACTION_ACL_EDIT : self::ACTION_ACL_ADD) || $this->renderJson(401, '无权限');

        $fields = $this->getFields();
        $label = $data = [];
        foreach ($fields as $field) {
            if ($field['field__auto_inc']) {
                continue;
            }
            if (mb_stripos($field['Type'], 'int') !== false) {
                $this->validate->rule('int', $field['Field']);
                $data[$field['Field']] = Input::requestInt($field['Field']);
            } else {
                $this->validate->rule('require', $field['Field']);
                $data[$field['Field']] = Input::requestString($field['Field']);
            }
            $label[$field['Field']] = $field['field__label'];
        }
        $this->validate->label($label);
        if (!$this->validate->validate()) {
            $this->validate->getErrors(2);
        }

        $pk ? $this->modelInst->updateByColumn($pk, $data) : $pk = $this->modelInst->set($data);
        LogService::addActionLog(($pk ? '修改' : '新增') . $this->model . $pk);

        $this->renderJson(0, '操作成功', $data);
    }

    /**
     * 显示条件筛选
     *
     */
    private function search()
    {
        $view = View::getEngine('Html');
        return $view->setHtmlEngineOptions('templateDir', dirname(__DIR__) . '/View/Auto/')
            ->assign('whereMap', json_encode($this->optMap, JSON_UNESCAPED_UNICODE))
            ->assign('fields', json_encode($this->getFields(), JSON_UNESCAPED_UNICODE))
            ->assign('default_field', $this->getFields()[0]['Field'])
            ->fetch('search', false, true, true);
    }

    /**
     * 删除
     *
     * @acljump ./index
     */
    public function del()
    {
        $this->hadActionAcl(self::ACTION_ACL_DEL) || $this->renderJson(401, '无权限');

        $ids = Input::getString('id');
        $ids = array_filter(explode('|', $ids), function ($id) {
            return !empty($id);
        });
        empty($ids) && $this->renderJson(1, '请选择要删除的项');

        $this->modelInst->whereIn($this->pkFieldName, $ids)->delByColumn(1, '1');

        LogService::addActionLog('删除了' . implode($ids, ','));
        $this->renderJson(0);
    }

    /**
     * 导出
     *
     * @acljump ./index
     */
    public function export()
    {
        $export = $this->ajaxPage('exportss');
        $field = array_keys($export[0]);
        View::getEngine('excel')->assign($export)
            ->display(date('Y-m-d'), $field);
    }

    /**
     * 获取字段
     *
     * @param bool $isGrid 列表页还是表单页
     *
     * @return array
     */
    protected function getFields($isGrid = true)
    {
        $stmt = $this->modelInst->db()->prepare("SHOW FULL COLUMNS FROM " . $this->modelInst->getTableName(true));
        $this->modelInst->execute($stmt, false);
        $fields = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($fields as $key => $item) {
            if ($this->showFields && !in_array($item['Field'], $this->showFields)) {
                unset($fields[$key]);
                continue;
            }

            if (in_array($item['Field'], $this->hideFields)) {
                unset($fields[$key]);
                continue;
            }

            if (isset($this->fieldLabel[$item['Field']])) {
                $fields[$key]['field__label'] = $this->fieldLabel[$item['Field']];
            } else if (isset($fields[$key]['Comment']) && $fields[$key]['Comment']) {
                $fields[$key]['field__label'] = $item['Field'] . '(' . trim($item['Comment']) . ')';
            } else {
                $fields[$key]['field__label'] = $item['Field'];
            }

            $fields[$key]['field__auto_inc'] = mb_stripos($item['Extra'], 'auto_increment') !== false;

            $fields[$key]['field__type'] = $isGrid ? ColumnType::Text : InputType::Text;
            if (isset($this->fieldType[$item['Field']])) {
                $fields[$key]['field__type'] = $this->fieldType[$item['Field']];
                if (!$isGrid) {
                    switch ($fields[$key]['field__type']) {
                        case ColumnType::Link:
                            $fields[$key]['field__type'] = InputType::File;
                            break;
                        case ColumnType::Image:
                            $fields[$key]['field__type'] = InputType::Image;
                            break;
                        case ColumnType::Date:
                            $fields[$key]['field__type'] = InputType::Date;
                            break;
                        case ColumnType::DateTime:
                            $fields[$key]['field__type'] = InputType::DateTime;
                            break;
                        default:
                            $fields[$key]['field__type'] = ColumnType::Text;

                    }
                }
                continue;
            }
            if (substr($item['Type'], 0, 8) == 'datetime') {
                $fields[$key]['field__type'] = $isGrid ? ColumnType::DateTime : InputType::DateTime;
            } else if (substr($item['Type'], 0, 4) == 'date') {
                $fields[$key]['field__type'] = $isGrid ? ColumnType::Date : InputType::Date;
            } else if (mb_stripos($item['Type'], 'text') !== false || mb_stripos($item['Type'], 'blob') !== false) {
                $isGrid || $fields[$key]['field__type'] = InputType::Textarea;
            }
        }

        return array_values($fields);
    }

    /**
     * 是否有某个操作的权限
     *
     * @param int $action 权限标志位
     *
     * @return bool
     */
    private function hadActionAcl($action = self::ACTION_ACL_ADD)
    {
        if (isset($this->aclAction[$action]) && (
                Acl::isSuperUser()
                || empty($this->aclAction[$action])//不限制用户组
                || array_intersect($this->aclAction[$action], Acl::$authUser['groupid'])
            )
        ) {
            return true;
        }
        return false;
    }
}
