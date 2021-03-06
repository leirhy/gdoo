<?php namespace Gdoo\Order\Controllers;

use DB;
use Request;
use Validator;

use Gdoo\Model\Grid;
use Gdoo\Model\Form;

use Gdoo\Order\Models\CustomerOrderType;

use Gdoo\Index\Controllers\DefaultController;

class TypeController extends DefaultController
{
    public $permission = ['dialog'];

    public function indexAction()
    {
        $header = Grid::header([
            'code' => 'customer_order_type',
            'referer' => 1,
            'search' => ['by' => ''],
            'trash_btn' => 0,
        ]);

        $cols = $header['cols'];

        $cols['actions']['options'] = [[
            'name' => '编辑',
            'action' => 'edit',
            'display' => $this->access['edit'],
        ]];

        $search = $header['search_form'];
        $query = $search['query'];

        if (Request::method() == 'POST') {
            $model = DB::table($header['table'])->setBy($header);
            foreach ($header['join'] as $join) {
                $model->leftJoin($join[0], $join[1], $join[2], $join[3]);
            }
            $model->orderBy($header['sort'], $header['order'])
            ->orderBy('id', 'desc');

            foreach ($search['where'] as $where) {
                if ($where['active']) {
                    $model->search($where);
                }
            }

            $model->select($header['select']);
            $rows = $model->paginate($query['limit'])->appends($query);
            $items = Grid::dataFilters($rows, $header);
            return $items->toJson();
        }

        $header['buttons'] = [
            ['name' => '删除', 'icon' => 'fa-remove', 'action' => 'delete', 'display' => $this->access['delete']],
            ['name' => '导出', 'icon' => 'fa-share', 'action' => 'export', 'display' => 1],
        ];
        $header['cols'] = $cols;
        $header['tabs'] = CustomerOrderType::$tabs;
        $header['bys']  = CustomerOrderType::$bys;
        $header['js']   = Grid::js($header);

        return $this->display([
            'header' => $header,
        ]);
    }

    // 新建客户联系人
    public function createAction()
    {
        $id = (int)Request::get('id');
        $form = Form::make(['code' => 'customer_order_type', 'id' => $id]);
        return $this->render([
            'form' => $form,
        ], 'create');
    }

    // 创建客户联系人
    public function editAction()
    {
        return $this->createAction();
    }

        /**
     * 弹出层信息
     */
    public function dialogAction()
    {
        $header = Grid::header([
            'code' => 'customer_order_type',
        ]);
        $search = $header['search_form'];
        $query = $search['query'];

        if (Request::method() == 'POST') {
            $model = CustomerOrderType::query();
            foreach ($header['join'] as $join) {
                $model->leftJoin($join[0], $join[1], $join[2], $join[3]);
            }
            $model->where('customer_order_type.status', 1)
            ->orderBy($header['sort'], $header['order']);

            foreach ($search['where'] as $where) {
                if ($where['active']) {
                    $model->search($where);
                }
            }

            $model->select($header['select']);

            $rows = $model->paginate($query['limit']);
            $items = Grid::dataFilters($rows, $header, function($item) {
                $item['text'] = $item['name'];
                return $item;
            });
            return response()->json($items);
        }
        $query['form_id'] = $query['jqgrid'] == '' ? $query['id'] : $query['jqgrid'];
        return $this->render([
            'search' => $search,
            'query'  => $query,
        ]);
    }

    // 删除
    public function deleteAction()
    {
        if (Request::method() == 'POST') {
            $ids = Request::get('id');
            return Form::remove(['code' => 'customer_order_type', 'ids' => $ids]);
        }
    }
}
