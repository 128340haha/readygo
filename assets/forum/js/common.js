function getTinyMceSetting(n,t){return t=$.extend(tinyMceCommonSettingsFull,t||{}),n&&(t=$.extend(t,{convert_urls:!0,relative_urls:!0,document_base_url:n})),t}var site_lang="zh_CN",tinyMceCommonSettingsFull;$(function(){typeof _pageinit=="function"&&_pageinit();typeof msg!="undefined"&&msg&&$.showMessageDialog(msg,["clear","ok","error","block","tip"][msgid]);typeof $.initializeJQueryUi=="function"&&$.initializeJQueryUi();$(".popupover").popover();$(".popuptip").bsTooltip({trigger:"hover"})});window.tinyMCEPreInit={suffix:"",base:"/resources/editor/tinymce",query:""};window.nicEditIcon="/resources/editor/niceditor/icon.gif";tinyMceCommonSettingsFull={theme:"modern",image_advtab:!0,selector:"textarea",language:site_lang,toolbar1:"insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | print preview media | forecolor backcolor emoticons",plugins:["advlist autolink lists link image charmap print preview hr anchor pagebreak","searchreplace wordcount visualblocks visualchars code fullscreen","insertdatetime media nonbreaking save table contextmenu directionality","emoticons template paste textcolor"],convert_urls:!1,content_css:"/content/style/page_editor.css",extended_valid_elements:"iframe[*]",entity_encoding:"raw"}