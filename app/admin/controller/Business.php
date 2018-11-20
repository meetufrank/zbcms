<?php
namespace app\admin\controller;
use think\Db;
use think\Request;
use clt\Form;
use app\common\logic\OprateLogic;
use app\common\logic\Taglogic;
class Business extends Common{
    protected  $dao,$fields;
    public function _initialize()
    {
        parent::_initialize();
        $this->moduleid = $this->mod[MODULE_NAME];
        $this->dao = db(MODULE_NAME);
        $fields = F($this->moduleid.'_Field');
        if(is_array($fields)){
          foreach($fields as $key => $res){
            $res['setup']=string2array($res['setup']);
            $this->fields[$key]=$res;
          }  
        }
        
        //查询有效的额动态标签列表
        $taglist=Taglogic::getInstance()->valid_select('id,name');
        if(is_array($taglist)){
           foreach ($taglist as $key => $value) {
                 $taglist[$key]['content']='{:gettag('.$value['id'].')}';
           } 
        }
        
        unset($fields);
        unset($res);
        $this->assign ('taglist',$taglist);
        $this->assign ('fields',$this->fields);
    }
    public function index(){
        if(request()->isPost()){
            $request = Request::instance();
            $modelname = MODULE_NAME;
            $model = db($modelname);
            $keyword=input('post.key');
            $page =input('page')?input('page'):1;
            $pageSize =input('limit')?input('limit'):config('pageSize');
            $order = "listorder asc,id desc";
            if (input('post.catid')) {
                $catids = db('category')->where('parentid',input('post.catid'))->column('id');
                if($catids){
                    $catid = input('post.catid').','.implode(',',$catids);
                }else{
                    $catid = input('post.catid');
                }
            }

            if(!empty($keyword) ){
                $map['name']=array('like','%'.$keyword.'%');
            }
            $prefix=config('database.prefix');
            $Fields=Db::getFields($prefix.$modelname);
            foreach ($Fields as $k=>$v){
                $field[$k] = $k;
            }
            if(in_array('catid',$field)){
               $map['catid']=array('in',$catid);
            }
            $map['deletetime']=0;
            $list = $model
                ->where($map)
                ->order($order)
                ->paginate(array('list_rows'=>$pageSize,'page'=>$page))
                ->toArray();
            //echo $model->getLastSql();
            $rsult['code'] = 0;
            $rsult['msg'] = lang('Get success');
            $lists = $list['data'];
            $setup= $this->fields['type'];
            $setup_arr=getsetup($setup);
            foreach ($lists as $k=>$v ){
                $lists[$k]['typename']=$setup_arr[$lists[$k]['type']];
                $lists[$k]['createtime'] = date('Y-m-d H:i:s',$v['createtime']);
            }
            
            $rsult['data'] = $lists;
            $rsult['count'] = $list['total'];
            $rsult['rel'] = 1;
            return $rsult;
        }else{
            return $this->fetch ();
        }
    }
    /*
     * 回收站
     */
      public function recindex(){
        if(request()->isPost()){
            $request = Request::instance();
            $modelname = MODULE_NAME;
            $model = db($modelname);
            $keyword=input('post.key');
            $page =input('page')?input('page'):1;
            $pageSize =input('limit')?input('limit'):config('pageSize');
            $order = "listorder asc,deletetime desc";
            if (input('post.catid')) {
                $catids = db('category')->where('parentid',input('post.catid'))->column('id');
                if($catids){
                    $catid = input('post.catid').','.implode(',',$catids);
                }else{
                    $catid = input('post.catid');
                }
            }

            if(!empty($keyword) ){
                $map['name']=array('like','%'.$keyword.'%');
            }
            $prefix=config('database.prefix');
            $Fields=Db::getFields($prefix.$modelname);
            foreach ($Fields as $k=>$v){
                $field[$k] = $k;
            }
            if(in_array('catid',$field)){
               $map['catid']=array('in',$catid);
            }
            $map['deletetime']=['neq',0];
            
            $list = $model
                ->where($map)
                ->order($order)
                ->paginate(array('list_rows'=>$pageSize,'page'=>$page))
                ->toArray();
            //echo $model->getLastSql();
            $rsult['code'] = 0;
            $rsult['msg'] = lang('Get success');
            $lists = $list['data'];
            $setup= $this->fields['type'];
            $setup_arr=getsetup($setup);
            foreach ($lists as $k=>$v ){
                $lists[$k]['typename']=$setup_arr[$lists[$k]['type']];
                $lists[$k]['deletetime'] = date('Y-m-d H:i:s',$v['deletetime']);
            }
            $rsult['data'] = $lists;
            $rsult['count'] = $list['total'];
            $rsult['rel'] = 1;
            return $rsult;
        }else{
            return $this->fetch ();
        }
    }
    
//设置状态
    public function usersState(){
        $id=input('post.id');
        $is_lock=input('post.is_open');
        if($this->dao->where('id='.$id)->update(['is_open'=>$is_lock])!==false){
            return ['status'=>1,'msg'=> lang('Successfully set up')];
        }else{
            return ['status'=>0,'msg'=> lang('Setup failed')];
        }
    }
    public function edit(){
        $id = input('id');
        $request = Request::instance();
        $controllerName = MODULE_NAME;
        
        if($controllerName=='Page'){
            $p = $this->dao->where('id',$id)->find();
            if(empty($p)){
                $data['id']=$id;
                $data['title'] = $this->categorys[$id]['catname'];
                $data['keywords'] = $this->categorys[$id]['keywords'];
                $this->dao->insert($data);
            }
        }
        $info = $this->dao->where('id',$id)->find();
        $form=new Form($info);
        $returnData['vo'] = $info;
        $returnData['form'] = $form;
        $this->assign ('info', $info );
        $this->assign ( 'form', $form );
        $this->assign ( 'title', lang('Edit content') );
        return $this->fetch();
    }
    function update(){
        $request = Request::instance();

        $controllerName = MODULE_NAME;
        $model = $this->dao;
        $fields = $this->fields;
        $data = $this->checkfield($fields,input('post.'));
        if($data['code']=="0"){
            $result['msg'] = $data['msg'];
            $result['code'] = 0;
            return $result;
        }
        

        if(isset($fields['updatetime'])) {
            $data['updatetime'] = time();
        }

        $title_style ='';
        if (isset($data['style_color'])) {
            $title_style .= 'color:' . $data['style_color'].';';
            unset($data['style_color']);
        }else{
            $title_style .= 'color:#222;';
        }
        if (isset($data['style_bold'])) {
            $title_style .= 'font-weight:' . $data['style_bold'].';';
            unset($data['style_bold']);
        }else{
            $title_style .= 'font-weight:normal;';
        }
        if($fields['title']['setup']['style']==1) {
            $data['title_style'] = $title_style;
        }
        if($controllerName!='Page') {
            $data['updatetime'] = time();
        }
     
        unset($data['pics_name']);
        //编辑多图和多文件
        foreach ($fields as $k=>$v){
            if($v['type']=='files' or $v['type']=='images'){
                if(!$data[$k]){
                    $data[$k]='';
                }
                $data[$v['field']] = $data['images'];
            }
        }
        $where=[
            'id'=>['neq',$data['id']]
        ];
        
        $only=$this->isonly($data,$where);
        if(!$only){
            $result['msg'] = lang('Business Management').lang('name').lang('Occupied');
            $result['code'] = 0;
            return $result;
        }
        $list=$model->update($data);
        if (false !== $list) {
            OprateLogic::getInstance()->insert(lang('Business Management').lang('colon').lang('Information changes'),$data['id']);   //存储操作日志
            if($controllerName=='Page'){
                $result['url'] = url("admin/category/index");
            }else{
                $result['url'] = url("admin/".$controllerName."/index",array('catid'=>$data['catid']));
            }
            $result['msg'] = lang('Successfully modified');
            $result['code'] = 1;
            return $result;
        } else {
            $result['msg'] = lang('fail to edit');
            $result['code'] = 0;
            return $result;
        }
    }
    public function set_categorys($categorys = array()) {
        if (is_array($categorys) && !empty($categorys)) {
            foreach ($categorys as $id => $c) {
                $this->categorys[$c['id']] = $c;
                $r = db('category')->where("parentid = $c[id]")->order('listorder ASC,id ASC')->select();
                $this->set_categorys($r);
            }
        }
        return true;
    }
    function checkfield($fields,$post){
        foreach ( $post as $key => $val ) {
            if(isset($fields[$key])){
                $setup=$fields[$key]['setup'];
                if(!empty($fields[$key]['required']) && empty($post[$key])){
                    $result['msg'] = $fields[$key]['errormsg']?$fields[$key]['errormsg']:lang('Missing necessary parameters').'！';
                    $result['code'] = 0;
                    return $result;
                }
                if(isset($setup['multiple'])){
                    if(is_array($post[$key])){
                        $post[$key] = implode(',',$post[$key]);
                    }
                }
                if(isset($setup['inputtype'])){
                    if($setup['inputtype']=='checkbox' || $fields[$key]['type']=='checkbox_db'){
                        $post[$key] = implode(',',$post[$key]);
                    }
                }
                if(isset($setup['fieldtype'])){
                    if($fields[$key]['type']=='checkbox' || $fields[$key]['type']=='checkbox_db'){
                        $post[$key] = implode(',',$post[$key]);
                    }
                }
                if($fields[$key]['type']=='datetime'){
                    $post[$key] =strtotime($post[$key]);
                }elseif($fields[$key]['type']=='textarea'){
                    $post[$key]=addslashes($post[$key]);
                }elseif($fields[$key]['type']=='editor'){
                    if(isset($post['add_description']) && $post['description'] == '' && isset($post['content'])) {
                        $content = stripslashes($post['content']);
                        $description_length = intval($post['description_length']);
                        $post['description'] = str_cut(str_replace(array("\r\n","\t",'[page]','[/page]','&ldquo;','&rdquo;'), '', strip_tags($content)),$description_length);
                        $post['description'] = addslashes($post['description']);
                    }
                    if(isset($post['auto_thumb']) && $post['thumb'] == '' && isset($post['content'])) {
                        $content = $content ? $content : stripslashes($post['content']);
                        $auto_thumb_no = intval($post['auto_thumb_no']) * 3;
                        if(preg_match_all("/(src)=([\"|']?)([^ \"'>]+\.(gif|jpg|jpeg|bmp|png))\\2/i", $content, $matches)) {
                            $post['thumb'] = $matches[$auto_thumb_no][0];
                        }
                    }
                }
            }
        }
        return $post;
    }

    public function add(){
        $form=new Form();
        $this->assign ( 'form', $form );
        $this->assign ( 'title', lang('Add content') );
        return $this->fetch('edit');
    }
    public function insert(){
        $request = Request::instance();
        $controllerName = MODULE_NAME;
        $model = $this->dao;
        $fields = $this->fields;
        $data = $this->checkfield($fields,input('post.'));
        if(isset($data['code']) && $data['code']==0){
            return $data;
        }
        if($fields['createtime']  && empty($data['createtime']) ){
            $data['createtime'] = time();
        }
        if($fields['updatetime']  && empty($data['updatetime']) ) {
            $data['updatetime'] = time();
        }
        if($controllerName!='Page') {
            if ($fields['updatetime']){
                $data['updatetime'] = $data['createtime'];
            }
        }
     
        $title_style ='';
        if (isset($data['style_color'])) {
            $title_style .= 'color:' . $data['style_color'].';';
            unset($data['style_color']);
        }else{
            $title_style .= 'color:#222;';
        }
        if (isset($data['style_bold'])) {
            $title_style .= 'font-weight:' . $data['style_bold'].';';
            unset($data['style_bold']);
        }else{
            $title_style .= 'font-weight:normal;';
        }
        if($fields['title']['setup']['style']==1) {
            $data['title_style'] = $title_style;
        }

       
        unset($data['style_color']);

        unset($data['pics_name']);
        //编辑多图和多文件
        foreach ($fields as $k=>$v){
            if($v['type']=='files' or $v['type']=='images'){
                if(!$data[$k]){
                    $data[$k]='';
                }
                $data[$v['field']] = $data['images'];
            }
        }
        $only=$this->isonly($data);
        if(!$only){
            $result['msg'] = lang('Business Management').lang('name').lang('Occupied');
            $result['code'] = 0;
            return $result;
        }
        $id= $model->insertGetId($data);
        if ($id !==false) {
            OprateLogic::getInstance()->insert(lang('Business Management').lang('colon').lang('Information added'),$id);   //存储操作日志
            $catid = $controllerName =='page' ? $id : $data['catid'];

            
            if($controllerName=='page'){
                $result['url'] = url("admin/category/index");
            }else{
                $result['url'] = url("admin/".$controllerName."/index",array('catid'=>$data['catid']));
            }
            $result['msg'] = lang('Added successfully');
            $result['code'] = 1;
            return $result;
        } else {
            $result['msg'] = lang('add failed');
            $result['code'] = 0;
            return $result;
        }

    }
        /*
     * 验证唯一操作
     * 
     */
    public function isonly($data,$where=[]) {
        $map=[
            'deletetime'=>0
        ];
        if(!empty($where)){
            foreach ($where as $key => $value) {
                $map[$key]=$value;
            }
        }
        $model=$this->dao;
        $map['name']=$data['name'];    
        $map['type']=$data['type'];  //名称和类型唯一
        $count=$model->where($map)->count();
        if($count){
            return false;
        }else{
            return true;
        }
    }
/*
 * 还原操作
 */
  public function reduction(){
      if(empty(input('post.id'))){
           return ['code'=>0,'msg'=>lang('Unchecked data')];      
        }
        $id = input('post.id');
        $model = $this->dao;
        $data=[
            'deletetime'=>0
        ];
        $model->where(array('id'=>$id))->update($data);//还原
        OprateLogic::getInstance()->insert(lang('Business Management').lang('colon').lang('Information restoration'),$id);   //存储操作日志
        return ['code'=>1,'msg'=>lang('Restored successfully')];
    }
    public function listDel(){
        if(empty(input('post.id'))){
           return ['code'=>0,'msg'=>lang('Unchecked data')];      
        }
        $id = input('post.id');
        $model = $this->dao;
        $data=[
            'deletetime'=>time()
        ];
        $model->where(array('id'=>$id))->update($data);//转入回收站
        OprateLogic::getInstance()->insert(lang('Business Management').lang('colon').lang('Transferring information to recycle bin'),$id);   //存储操作日志
        return ['code'=>1,'msg'=>lang('successfully deleted')];
    }
    public function delAll(){
        if(empty(input('param.ids/a'))){
                $result['code'] =0;
                $result['msg'] = lang('Unchecked data');
                $result['url'] = url('index',array('catid'=>input('post.catid')));
                return $result;
        }
        $map['id'] =array('in',input('param.ids/a'));
        $model = $this->dao;
        $data=[
            'deletetime'=>time()
        ];
        $model->where($map)->update($data);
        OprateLogic::getInstance()->insert(lang('Business Management').lang('colon').lang('Bulk transfer to recycle bin'), implode(',',input('param.ids/a')));   //存储操作日志
        $result['code'] = 1;
        $result['msg'] = lang('failed to delete');
        $result['url'] = url('index',array('catid'=>input('post.catid')));
        return $result;
    }
        public function listRemove(){
        if(empty(input('post.id'))){
           return ['code'=>0,'msg'=>lang('Unchecked data')];      
        }
        $id = input('post.id');
        $model = $this->dao;
        $model->where(array('id'=>$id))->delete();//彻底删除
        OprateLogic::getInstance()->insert(lang('Business Management').lang('colon').lang('Delete information completely'),$id);   //存储操作日志
        return ['code'=>1,'msg'=>lang('Deleted completely')];
    }
    public function removeAll(){
        if(empty(input('param.ids/a'))){
                $result['code'] =0;
                $result['msg'] = lang('Unchecked data');
                $result['url'] = url('index',array('catid'=>input('post.catid')));
                return $result;
        }
        $map['id'] =array('in',input('param.ids/a'));
        $model = $this->dao;
        
        $model->where($map)->delete();
        OprateLogic::getInstance()->insert(lang('Business Management').lang('colon').lang('Batch delete information completely'), implode(',',input('param.ids/a')));   //存储操作日志
        $result['code'] = 1;
        $result['msg'] = lang('Complete deletion failed');
        $result['url'] = url('index',array('catid'=>input('post.catid')));
        return $result;
    }
    /*
     * 批量还原
     */
       public function reductionAll(){
        if(empty(input('param.ids/a'))){
                $result['code'] =0;
                $result['msg'] = lang('Unchecked data');
                $result['url'] = url('index',array('catid'=>input('post.catid')));
                return $result;
        }
        $map['id'] =array('in',input('param.ids/a'));
        $model = $this->dao;
        $data=[
            'deletetime'=>0
        ];
        $model->where($map)->update($data);
        OprateLogic::getInstance()->insert(lang('Business Management').lang('colon').lang('Information batch restore'), implode(',',input('param.ids/a')));   //存储操作日志
        $result['code'] = 1;
        $result['msg'] = lang('Batch restore successful');
        $result['url'] = url('index',array('catid'=>input('post.catid')));
        return $result;
    }
    public function listorder(){
        $model = $this->dao;
        $catid = input('catid');
        $data = input('post.');
        $model->update($data);
        $result = ['msg' => lang('Sorting success'),'url'=>url('index',array('catid'=>$catid)), 'code' => 1];
        return $result;
    }
    public function delImg(){
        if(!input('post.url')){
            return ['code'=>0,lang('Please specify the image resource to delete')];
        }
        $file = ROOT_PATH.__PUBLIC__.input('post.url');
        if(file_exists($file) && trim(input('post.url'))!=''){
            is_dir($file) ? dir_delete($file) : unlink($file);
        }
        if(input('post.id')){
            $picurl = input('post.picurl');
            $picurlArr = explode(':',$picurl);
            $pics = substr(implode(":::",$picurlArr),0,-3);
            $model = $this->dao;
            $map['id'] =input('post.id');
            $model->where($map)->update(array('pics'=>$pics));
        }
        $result['msg'] = lang('successfully deleted');
        $result['code'] = 1;
        return $result;
    }
}
