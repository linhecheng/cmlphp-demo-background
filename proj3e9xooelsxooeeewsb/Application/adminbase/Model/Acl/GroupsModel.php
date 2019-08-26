<?php
namespace adminbase\Model\Acl;

use Cml\Model;
use Cml\Vendor\Acl;

class GroupsModel extends Model
{
    protected $table = 'admin_groups';

    public function __construct()
    {
        $this->table = Acl::getTableName('groups');
    }

    /**
     * 获取总数
     *
     * @return mixed
     */
    public function getTotalNums()
    {
        $this->db()->where('status', 1);
        return parent::getTotalNums();
    }

    /**
     * 获取所有用户组
     *
     * @return array
     */
    public function getAllGroups()
    {
        return $this->mapDbAndTable()
            ->where('status', 1)
            ->select();
    }

    /**
     * 获取用户组列表
     *
     * @param int $limit
     *
     * @return array
     */
    public function getGroupsList($limit = 20)
    {
        $this->db()->where('status', 1);
        return parent::getListByPaginate($limit);
    }
}