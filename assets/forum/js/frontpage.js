function _pageinit(){typeof _history!="undefined"&&initHistory();typeof _download!="undefined"&&initDownload();typeof _softid!="undefined"&&initSoftCounter();initDonationList()}function initDonationList(){var n=$(".donationListContainer");n.length!=0&&(n.html("<img src='"+_static_resource_root+"/resources/images/16px_loading_1.gif' /> 正在加载列表中，请稍等...."),$.xpost(_donationApi,{id:_cursoftid},"json",function(t){var i=[],r;i.push("<table class='table table-striped table-hover table-bordered'><tr><th>大名<\/th><th>日期<\/th><\/tr>");r=0;$.each(t,function(){i.push("<tr><td>"+this.name+"<\/td><td>"+this.date+"<\/td><\/tr>")});i.push("<\/table>");n.html(i.join(""))}))}function doAjax(n,t,i,r,u,f){var o=this,e=$.createLoadingDialog(i),s=function(n){n=n||{success:!1,message:"未知错误"};e.changeLoadingIcon(n.success?"ok":"block");e.setLoadingMessage(n.message);e.autoCloseDialog();n.success?typeof u=="function"&&u.call(o,n):typeof f=="function"&&f.call(o,n)},h=function(){$.post(n,t,s,"json")};e.showDialog({onShow:h,bindControl:r})}function initSoftCounter(){var n=!1,t={id:_softid.toString()},i=function(t){var r,i;for(r in t)i=t[r],$("span.downloadCounter[sid="+i.id+"]").html(i._dc),$("span.updateCheckCounter[sid="+i.id+"]").html(i._ucc),$("span.updateCounter[sid="+i.id+"]").html(i._uc);n=!1},r=function(){n||(n=!0,$.post(_softCounterApi,t,i,"json"))};r()}function initHistory(){for(var t=[],n,i=0;i<_history.length;i++)n=_history[i],t.push("<div class='historyEntry'><div class='title'>"+n.Title+"<span>版本: <span class='nb'>"+n.Version+"<\/span>，更新时间： <span class='nb'>"+n.Time+"<\/span><\/span><\/div>"),t.push(encodeUpdateList(n.Content)),t.push("<\/div>");$(".historyContainer").html(t.join("")).show()}function encodeUpdateList(n){n=n.split("\n");var i=/^\s*(\[?(\+|\-|\*|!|\>)\]?)?\s*(.*)$/,t=[];return t.push("<ul>"),$.each(n,function(){if(!this)return!0;if(this.indexOf("----")!=-1)return t.push("<li class='blank'>"+this+"<\/li>"),!0;if(i.exec($.trim(this))){var n="";switch(RegExp.$2){case"+":n="add";break;case"-":n="remove";break;case"*":n="fix";break;case"!":n="warn";break;case">":n="pre";break;case"<":n="rollback";break;default:n=""}t.push("<li class='"+n+"'>"+RegExp.$3+"<\/li>")}else t.push("<li>"+this+"<\/li>");return!0}),t.push("<\/ul>"),t.join("")}function initDownload(){var n=[],i,r,t;n.push("<table>");i=[];for(r in _download)(t=_download[r],t.ID)&&(i.push(t.ID),n.push("<tr><td class='downloadBtnContainer'><a class='downloadButton' href='"),n.push(_downloadUrl.replace("!1",t.ID).replace("@name",t.FileName)),n.push("' rel='nofollow'><h6>"),n.push(t.Title?t.Title:"本地下载"),n.push("<\/h6><p>已有 <span id='dc_"+t.ID+"'><\/span> 次下载<\/p><\/a>"),n.push("<div class='updateTime'><span>更新时间：<\/span>"+t.Update+"<\/div>"),n.push("<div class='downloadSize'><span>下载大小：<\/span>"+t.Size+"<\/div>"),n.push("<\/td><td>"),n.push(t.Remark?t.Remark:"(暂时没有说明)"),n.push("<\/td><\/tr>"));n.push("<tr><td colspan='1'><a href='/service/softarchive/"+_cursoftid+"' id='downloadHistory' class='btn btn-default'><i class='glyphicon glyphicon glyphicon-download-alt'><\/i> 所有可供下载的版本<\/a><\/td><td>提供了所有可供下载的版本<\/td><\/tr>");n.push("<\/table>");$(".downloadContainer").html(n.join("")).show();i.length>0&&(_idlist={id:i.toString()},setupDownloadCountUpdater())}function setupDownloadCountUpdater(){if(!_inajax){_inajax=!0;var n=function(n){var i,t;for(i in n)t=n[i],$("#dc_"+t.id).html(t._c);_inajax=!1};$.post(_downCounterApi,_idlist,n,"json")}}function addEditor(n){return $(n).css({height:"300px",width:"100%"}).xheditor({tools:"full",skin:"o2007blue",emotPath:_static_resource_root+"/resources/emot/"})}var _inajax=!1,_idlist;$(_pageinit),function(){$("a[href$='.mxaddon']").on("click",function(){return typeof external.mxCall=="undefined"?($.showMessageDialog("很抱歉，此扩展包需要<strong>遨游3<\/strong>，您没有安装或版本过低。请使用其它下载点。","block"),!1):(external.mxCall("InstallApp",location.origin+$(this).attr("href")),!1)})}()