<?php
/**
 * 菜单管理
 *
 */
namespace adminbase\Model\Acl;

use Cml\Model;
use Cml\Vendor\Acl;

class MenusModel extends Model
{
    protected $table = 'admin_menus';

    public function __construct()
    {
        $this->table = Acl::getTableName('menus');
    }

    /**
     * 判断是否存在子菜单
     *
     * @param int $id
     *
     * @return bool
     */
    public function hasSonMenus($id)
    {
        return $this->mapDbAndTable()
            ->where('pid', $id)
            ->count('id') > 0;
    }
}