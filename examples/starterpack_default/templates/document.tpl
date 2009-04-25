<html>
  <head>
    <title>{$title|escape}</title>
{foreach from=$styles item=style}
    <link rel="stylesheet" href="{$style|escape}" />
{/foreach}
{foreach from=$scripts item=script}
    <script type="text/javascript" src="{$script|escape}"></script>
{/foreach}
  </head>
  <body>
    {$content}
  </body>
{foreach from=$onload item=javascript}
    <script type="text/javascript">
      {$javascript}
    </script>
{/foreach}
</html>
