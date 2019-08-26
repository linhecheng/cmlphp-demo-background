<?php
namespace adminbase\Model\Acl;

use Cml\Model;
use Cml\Vendor\Acl;

class AccessModel extends Model
{
    protected $table = 'admin_access';

    public function __construct()
    {
        $this->table = Acl::getTableName('access');
    }

    /**
     * 通过字段获取有权限的菜单id数组
     *
     * @param array | int $id
     * @param string $field
     *
     * @return array
     */
    public function getAccessArrByField($id, $field = 'groupid')
    {
        is_array($id) || $id = [$id];
        return $this->mapDbAndTable()
            ->whereIn($field, $id)
            ->columns('menuid')
            ->select(0, 5000);
    }
}