<?php
/**
 * 图集发布
 *
 */
//require_once(dirname(__FILE__)."/config.php");
//CheckPurview('a_New,a_AccNew');
//******mycode***************************************************//

define('DEDEADMIN', str_replace("\\", '/', dirname(__FILE__) ) );
require_once(DEDEADMIN.'/../include/common.inc.php');
require_once(DEDEINC.'/userlogin.class.php');
header('Cache-Control:private');
$dsql->safeCheck = FALSE;
$dsql->SetLongLink();
$cfg_admin_skin = 1; // 后台管理风格

if(file_exists(DEDEDATA.'/admin/skin.txt'))
{
    $skin = file_get_contents(DEDEDATA.'/admin/skin.txt');
    $cfg_admin_skin = !in_array($skin, array(1,2,3,4))? 1 : $skin;
}

$_csrf_name = '_csrf_name_'.substr(md5(md5($cfg_cookie_encode)),0,8);
$_csrf_hash =  GetCookie($_csrf_name);
if ( empty($_csrf_hash) )
{
    $_csrf_hash = md5(uniqid(mt_rand(), TRUE));
    if (strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST')
    {
        PutCookie($_csrf_name, $_csrf_hash, 7200, '/');
    }
}

$_csrf =  array(
    'name'  =>'_dede'.$_csrf_name,
    'hash'  => $_csrf_hash,
);

//获得当前脚本名称，如果你的系统被禁用了$_SERVER变量，请自行更改这个选项
$dedeNowurl = $s_scriptName = '';
$isUrlOpen = @ini_get('allow_url_fopen');
$dedeNowurl = GetCurUrl();
$dedeNowurls = explode('?', $dedeNowurl);
$s_scriptName = $dedeNowurls[0];
$cfg_remote_site = empty($cfg_remote_site)? 'N' : $cfg_remote_site;

//检验用户登录状态
$cuserLogin = new userLogin();
$cuserLogin->userID = 1;
$cuserLogin->userName = "admin";
$cuserLogin->userType = 10;
$cuserLogin->userCPwd = "f297a57a5a743894a0e4";

// if($cuserLogin->getUserID()==-1)
// {
//     if ( preg_match("#PHP (.*) Development Server#",$_SERVER['SERVER_SOFTWARE']) )
//     {
//         $dirname = dirname($_SERVER['SCRIPT_NAME']);
//         header("location:{$dirname}/login.php?gotopage=".urlencode($dedeNowurl));
//     } else {
//         header("location:login.php?gotopage=".urlencode($dedeNowurl));
//     }
//     exit();
// }

// function csrf_check()
// {
//     global $token;

//     if(!isset($token) || strcasecmp($token, $_SESSION['token']) != 0){
//         echo '<a href="http://bbs.dedecms.com/907721.html">DedeCMS:CSRF Token Check Failed!</a>';
//         exit;
//     }
// }

// function XSSClean($val)
// {

//     if (is_array($val))
//     {
//         while (list($key) = each($val))
//         {
//             if(in_array($key,array('tags','body','dede_fields','dede_addonfields','dopost','introduce'))) continue;
//             $val[$key] = XSSClean($val[$key]);
//         }
//         return $val;
//     }
//     return RemoveXss($val);
// }

if($cfg_dede_log=='Y')
{
    $s_nologfile = '_main|_list';
    $s_needlogfile = 'sys_|file_';
    $s_method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';
    $s_query = isset($dedeNowurls[1]) ? $dedeNowurls[1] : '';
    $s_scriptNames = explode('/', $s_scriptName);
    $s_scriptNames = $s_scriptNames[count($s_scriptNames)-1];
    $s_userip = GetIP();
    if( $s_method=='POST' || (!preg_match("#".$s_nologfile."#i", $s_scriptNames) && $s_query!='') || preg_match("#".$s_needlogfile."#i",$s_scriptNames) )
    {
        $inquery = "INSERT INTO `#@__log`(adminid,filename,method,query,cip,dtime)
             VALUES ('".$cuserLogin->getUserID()."','{$s_scriptNames}','{$s_method}','".addslashes($s_query)."','{$s_userip}','".time()."');";
        $dsql->ExecuteNoneQuery($inquery);
    }
}

//启用远程站点则创建FTP类
if($cfg_remote_site=='Y')
{
    require_once(DEDEINC.'/ftp.class.php');
    if(file_exists(DEDEDATA."/cache/inc_remote_config.php"))
    {
        require_once DEDEDATA."/cache/inc_remote_config.php";
    }
    if(empty($remoteuploads)) $remoteuploads = 0;
    if(empty($remoteupUrl)) $remoteupUrl = '';
    $config = array(
      'hostname' => $GLOBALS['cfg_ftp_host'],
      'username' => $GLOBALS['cfg_ftp_user'],
      'password' => $GLOBALS['cfg_ftp_pwd'],
      'debug' => 'TRUE'
    );
    $ftp = new FTP($config); 

    //初始化FTP配置
    if($remoteuploads==1){
        $ftpconfig = array(
            'hostname'=>$rmhost, 
            'port'=>$rmport,
            'username'=>$rmname,
            'password'=>$rmpwd
        );
    }
}

//管理缓存、管理员频道缓存
$cache1 = DEDEDATA.'/cache/inc_catalog_base.inc';
if(!file_exists($cache1)) UpDateCatCache();
$cacheFile = DEDEDATA.'/cache/admincat_'.$cuserLogin->userID.'.inc';
if(file_exists($cacheFile)) require_once($cacheFile);

//更新服务器
require_once (DEDEDATA.'/admin/config_update.php');

// if(strlen($cfg_cookie_encode)<=10)
// {
//     $chars='abcdefghigklmnopqrstuvwxwyABCDEFGHIGKLMNOPQRSTUVWXWY0123456789';
//     $hash='';
//     $length = rand(28,32);
//     $max = strlen($chars) - 1;
//     for($i = 0; $i < $length; $i++) {
//         $hash .= $chars[mt_rand(0, $max)];
//     }
//     $dsql->ExecuteNoneQuery("UPDATE `#@__sysconfig` SET `value`='{$hash}' WHERE varname='cfg_cookie_encode' ");
//     $configfile = DEDEDATA.'/config.cache.inc.php';
//     if(!is_writeable($configfile))
//     {
//         echo "配置文件'{$configfile}'不支持写入，无法修改系统配置参数！";
//         exit();
//     }
//     $fp = fopen($configfile,'w');
//     flock($fp,3);
//     fwrite($fp,"<"."?php\r\n");
//     $dsql->SetQuery("SELECT `varname`,`type`,`value`,`groupid` FROM `#@__sysconfig` ORDER BY aid ASC ");
//     $dsql->Execute();
//     while($row = $dsql->GetArray())
//     {
//         if($row['type']=='number')
//         {
//             if($row['value']=='') $row['value'] = 0;
//             fwrite($fp,"\${$row['varname']} = ".$row['value'].";\r\n");
//         }
//         else
//         {
//             fwrite($fp,"\${$row['varname']} = '".str_replace("'",'',$row['value'])."';\r\n");
//         }
//     }
//     fwrite($fp,"?".">");
//     fclose($fp);
// }



/**
 *  引入模板文件
 *
 * @access    public
 * @param     string  $filename  文件名称
 * @param     bool  $isabs  是否为管理目录
 * @return    string
 */
function DedeInclude($filename, $isabs=FALSE)
{
    return $isabs ? $filename : DEDEADMIN.'/'.$filename;
}

/**
 *  获取当前用户的ftp站点
 *
 * @access    public
 * @param     string  $current  当前站点
 * @param     string  $formname  表单名称
 * @return    string
 */
function GetFtp($current='', $formname='')
{
    global $dsql;
    $formname = empty($formname)? 'serviterm' : $formname;
    $cuserLogin = new userLogin();
    $row=$dsql->GetOne("SELECT servinfo FROM `#@__multiserv_config`");
    $row['servinfo']=trim($row['servinfo']);
    if(!empty($row['servinfo'])){
        $servinfos = explode("\n", $row['servinfo']);
        $select="";
        echo '<select name="'.$formname.'" size="1" id="serviterm">';
        $i=0;
        foreach($servinfos as $servinfo){
            $servinfo = trim($servinfo);
            list($servname,$servurl,$servport,$servuser,$servpwd,$userlist) = explode('|',$servinfo);
            $servname = trim($servname);
            $servurl = trim($servurl);
            $servport = trim($servport);
            $servuser = trim($servuser);
            $servpwd = trim($servpwd);
            $userlist = trim($userlist);   
            $checked = ($current == $i)? '  selected="selected"' : '';
            if(strstr($userlist,$cuserLogin->getUserName()))
            {
                $select.="<option value='".$servurl.",".$servuser.",".$servpwd."'{$checked}>".$servname."</option>";  
            }
            $i++;
        }
        echo  $select."</select>";
    }
}
helper('cache');
/**
 *  根据用户mid获取用户名称
 *
 * @access    public
 * @param     int  $mid   用户ID
 * @return    string
 */
if(!function_exists('GetMemberName')){
    function GetMemberName($mid=0)
    {
        global $dsql;
        $rs = GetCache('memberlogin', $mid);
        if( empty($rs) )
        {
            $rs = $dsql->GetOne("SELECT * FROM `#@__member` WHERE mid='{$mid}' ");
            SetCache('memberlogin', $mid, $rs, 1800);
        }
        return $rs['uname'];
    }
}


//*******************************************************************************************************//
require_once(DEDEINC."/customfields.func.php");
require_once(DEDEADMIN."/inc/inc_archives_functions.php");

if(empty($dopost)) $dopost = '';

if($dopost != 'save')
{
    require_once(DEDEINC."/dedetag.class.php");
    require_once(DEDEADMIN."/inc/inc_catalog_options.php");
    ClearMyAddon();
    $channelid = empty($channelid) ? 0 : intval($channelid);
    $cid = empty($cid) ? 0 : intval($cid);

    //获得频道模型ID
    if($cid>0 && $channelid==0)
    {
        $row = $dsql->GetOne("SELECT channeltype FROM `#@__arctype` WHERE id='$cid'; ");
        $channelid = $row['channeltype'];
    }
    else
    {
        if($channelid==0) $channelid = 2;
    }

    //获得频道模型信息
    $cInfos = $dsql->GetOne(" SELECT * FROM  `#@__channeltype` WHERE id='$channelid' ");
    $channelid = $cInfos['id'];
    
    //获取文章最大id以确定当前权重
    $maxWright = $dsql->GetOne("SELECT COUNT(*) AS cc FROM #@__archives");
    include DedeInclude("templets/album_add.htm");
    exit();
}
/*--------------------------------
function __save(){  }
-------------------------------*/
else if($dopost=='save')
{
    require_once(DEDEINC.'/image.func.php');

    
    $flag = isset($flags) ? join(',',$flags) : '';
    $notpost = isset($notpost) && $notpost == 1 ? 1: 0;
    if(empty($click)) $click = ($cfg_arc_click=='-1' ? mt_rand(50, 200) : $cfg_arc_click);
    
    if(!isset($typeid2)) $typeid2 = 0;
    if(!isset($autokey)) $autokey = 0;
    if(!isset($remote)) $remote = 0;
    if(!isset($dellink)) $dellink = 0;
    if(!isset($autolitpic)) $autolitpic = 0;
    if(!isset($formhtml)) $formhtml = 0;
    if(!isset($formzip)) $formzip = 0;
    if(!isset($ddisfirst)) $ddisfirst = 0;
    if(!isset($delzip)) $delzip = 0;
    if(empty($click)) $click = ($cfg_arc_click=='-1' ? mt_rand(50, 200) : $cfg_arc_click);

    if($typeid==0)
    {
        ShowMsg("请指定文档的栏目！", "-1");
        exit();
    }
    if(empty($channelid))
    {
        ShowMsg("文档为非指定的类型，请检查你发布内容的表单是否合法！","-1");
        exit();
    }
    if(!CheckChannel($typeid,$channelid) )
    {
        ShowMsg("你所选择的栏目与当前模型不相符，请选择白色的选项！","-1");
        exit();
    }
    // if(!TestPurview('a_New'))
    // {
    //     CheckCatalog($typeid,"对不起，你没有操作栏目 {$typeid} 的权限！");
    // }

    //对保存的内容进行处理
    if(empty($writer))$writer=$cuserLogin->getUserName();
    if(empty($source))$source='未知';
    $pubdate = GetMkTime($pubdate);
    $senddate = time();
    $sortrank = AddDay($pubdate,$sortup);
    $ismake = $ishtml==0 ? -1 : 0;
    $title = preg_replace("#\"#", '＂', $title);
    $title = cn_substrR($title,$cfg_title_maxlen);
    $shorttitle = cn_substrR($shorttitle,36);
    $color =  cn_substrR($color,7);
    $writer =  cn_substrR($writer,20);
    $source = cn_substrR($source,30);
    $description = cn_substrR($description,$cfg_auot_description);
    $keywords = cn_substrR($keywords,60);
    $filename = trim(cn_substrR($filename,40));
    $userip = GetIP();
    $isremote  = (empty($isremote)? 0  : $isremote);
    $serviterm=empty($serviterm)? "" : $serviterm;
    $adminid = $cuserLogin->getUserID();

    //处理上传的缩略图
    if(empty($ddisremote))
    {
        $ddisremote = 0;
    }
    $litpic = GetDDImage('none',$picname,$ddisremote);

    //使用第一张图作为缩略图
    if($ddisfirst==1 && $litpic=='')
    {
        if(isset($imgurl1))
        {
            $litpic = GetDDImage('ddfirst', $imgurl1, $isrm);
        }
    }
    

    //生成文档ID
    $arcID = GetIndexKey($arcrank,$typeid,$sortrank,$channelid,$senddate,$adminid);
    if(empty($arcID))
    {
        ShowMsg("无法获得主键，因此无法进行后续操作！","-1");
        exit();
    }

    $imgurls = "{dede:pagestyle maxwidth='$maxwidth' pagepicnum='$pagepicnum' ddmaxwidth='$ddmaxwidth' row='$row' col='$col' value='$pagestyle'/}\r\n";
    $hasone = FALSE;


    /*---------------------
    function _getformupload()
    通过swfupload正常上传的图片
    ---------------------*/
    if(is_array($_SESSION['bigfile_info']))
    {
        foreach($_SESSION['bigfile_info'] as $k=>$v)
        {
            $truefile = $cfg_basedir.$v;
            if(strlen($v)<2 || !file_exists($truefile)) continue;
            $info = '';
            $imginfos = GetImageSize($truefile, $info);
            $litpicname = $pagestyle > 2 ? GetImageMapDD($v, $cfg_ddimg_width) : '';
            if(!$hasone && $ddisfirst==1 && $litpic=='')
            {
                 $litpic = empty($litpicname) ? GetImageMapDD($v, $cfg_ddimg_width) : $litpicname;
                 $hasone = TRUE;
            }
            $imginfo =  !empty(${'picinfook'.$k}) ? ${'picinfook'.$k} : '';
            $imgurls .= "{dede:img ddimg='$v' text='$imginfo' width='".$imginfos[0]."' height='".$imginfos[1]."'} $v {/dede:img}\r\n";
        }
    }

    $imgurls = addslashes($imgurls);
    
    //处理body字段自动摘要、自动提取缩略图等
    $body = AnalyseHtmlBody($body,$description,$litpic,$keywords,'htmltext');

    //分析处理附加表数据
    $inadd_f = '';
    $inadd_v = '';
    if(!empty($dede_addonfields))
    {
        $addonfields = explode(';',$dede_addonfields);
        $inadd_f = '';
        $inadd_v = '';
        if(is_array($addonfields))
        {
            foreach($addonfields as $v)
            {
                if($v=='')
                {
                    continue;
                }
                $vs = explode(',',$v);
                if(!isset(${$vs[0]}))
                {
                    ${$vs[0]} = '';
                }
                else if($vs[1]=='htmltext'||$vs[1]=='textdata') //HTML文本特殊处理
                {
                    ${$vs[0]} = AnalyseHtmlBody(${$vs[0]},$description,$litpic,$keywords,$vs[1]);
                }
                else
                {
                    if(!isset(${$vs[0]}))
                    {
                        ${$vs[0]} = '';
                    }
                    ${$vs[0]} = GetFieldValueA(${$vs[0]},$vs[1],$arcID);
                }
                $inadd_f .= ','.$vs[0];
                $inadd_v .= " ,'".${$vs[0]}."' ";
            }
        }
    }

    //处理图片文档的自定义属性
    if($litpic!='' && !preg_match("#p#", $flag))
    {
        $flag = ($flag=='' ? 'p' : $flag.',p');
    }
    if($redirecturl!='' && !preg_match("#j#", $flag))
    {
        $flag = ($flag=='' ? 'j' : $flag.',j');
    }

    //跳转网址的文档强制为动态
    if(preg_match("#j#", $flag)) $ismake = -1;
    //加入主档案表
    $query = "INSERT INTO `#@__archives`(id,typeid,typeid2,sortrank,flag,ismake,channel,arcrank,click,money,title,shorttitle,
     color,writer,source,litpic,pubdate,senddate,mid,notpost,description,keywords,filename,dutyadmin,weight)
    VALUES ('$arcID','$typeid','$typeid2','$sortrank','$flag','$ismake','$channelid','$arcrank','$click','$money','$title','$shorttitle',
    '$color','$writer','$source','$litpic','$pubdate','$senddate','$adminid','$notpost','$description','$keywords','$filename','$adminid','$weight'); ";
    if(!$dsql->ExecuteNoneQuery($query))
    {
        $gerr = $dsql->GetError();
        $dsql->ExecuteNoneQuery(" DELETE FROM `#@__arctiny` WHERE id='$arcID' ");
        ShowMsg("把数据保存到数据库主表 `#@__archives` 时出错，请把相关信息提交给DedeCms官方。".str_replace('"','',$gerr),"javascript:;");
        exit();
    }

    //加入附加表
    $cts = $dsql->GetOne("SELECT addtable FROM `#@__channeltype` WHERE id='$channelid' ");
    $addtable = trim($cts['addtable']);
    if(empty($addtable))
    {
        $dsql->ExecuteNoneQuery("DELETE FROM `#@__archives` WHERE id='$arcID'");
        $dsql->ExecuteNoneQuery("DELETE FROM `#@__arctiny` WHERE id='$arcID'");
        ShowMsg("没找到当前模型[{$channelid}]的主表信息，无法完成操作！。","javascript:;");
        exit();
    }
    $useip = GetIP();
    $query = "INSERT INTO `$addtable`(aid,typeid,redirecturl,userip,pagestyle,maxwidth,imgurls,row,col,isrm,ddmaxwidth,pagepicnum,body{$inadd_f})
         Values('$arcID','$typeid','$redirecturl','$useip','$pagestyle','$maxwidth','$imgurls','$row','$col','$isrm','$ddmaxwidth','$pagepicnum','$body'{$inadd_v}); ";
    if(!$dsql->ExecuteNoneQuery($query))
    {
        $gerr = $dsql->GetError();
        $dsql->ExecuteNoneQuery("DELETE FROM `#@__archives` WHERE id='$arcID'");
        $dsql->ExecuteNoneQuery("DELETE FROM `#@__arctiny` WHERE id='$arcID'");
        ShowMsg("把数据保存到数据库附加表 `{$addtable}` 时出错，请把相关信息提交给DedeCMS官方。".str_replace('"','',$gerr),"javascript:;");
        exit();
    }

    //生成HTML
    InsertTags($tags,$arcID);
    if($cfg_remote_site=='Y' && $isremote=="1")
    {    
        if($serviterm!=""){
            list($servurl,$servuser,$servpwd) = explode(',',$serviterm);
            $config=array( 'hostname' => $servurl, 'username' => $servuser, 'password' => $servpwd,'debug' => 'TRUE');
        }else{
            $config=array();
        }
        if(!$ftp->connect($config)) exit('Error:None FTP Connection!');
    }
    $artUrl = MakeArt($arcID, TRUE, TRUE, $isremote);
    if($artUrl=='')
    {
        $artUrl = $cfg_phpurl."/view.php?aid=$arcID";
    }
    ClearMyAddon($arcID, $title);
    //返回成功信息
    echo "success";
}