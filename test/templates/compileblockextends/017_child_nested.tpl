{extends file='017_parent.tpl'}
{block name='content1'}
 {block name='content2' force}
  child pre {$smarty.block.child} child post
 {/block}
{/block}
