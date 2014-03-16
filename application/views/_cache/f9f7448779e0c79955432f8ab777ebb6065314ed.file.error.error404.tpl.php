<?php /* Smarty version Smarty-3.1.13, created on 2014-03-15 11:40:34
         compiled from "C:\wamp\www\pbmovies\application\library\framework\views\error.error404.tpl" */ ?>
<?php /*%%SmartyHeaderCode:131453247472e32b41-58135892%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'f9f7448779e0c79955432f8ab777ebb6065314ed' => 
    array (
      0 => 'C:\\wamp\\www\\pbmovies\\application\\library\\framework\\views\\error.error404.tpl',
      1 => 1394898025,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '131453247472e32b41-58135892',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'showErrors' => 0,
    'error_type' => 0,
    'request' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.13',
  'unifunc' => 'content_53247473124db4_88610709',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_53247473124db4_88610709')) {function content_53247473124db4_88610709($_smarty_tpl) {?><div id="page_content">
    <h2>Page not found</h2>
    <p>
        <i class="icon-info-sign"></i> The page you're looking for was not found. Please try again later.
    </p>
    <?php if ($_smarty_tpl->tpl_vars['showErrors']->value){?>
        <p align="center"><strong>ERROR: </strong> <?php echo $_smarty_tpl->tpl_vars['error_type']->value;?>
</p>
        <pre>
Params: <?php echo $_smarty_tpl->tpl_vars['request']->value;?>

        </pre>
    <?php }?>
</div><?php }} ?>