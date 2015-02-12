# How To Use

## To add style or javascript in any place (Controller or View):

* Adding real existing files of styles on server or other host

        StaticCss::instance()->addCss('/css/admin.css');
        StaticCss::instance()->addCss('http://jquickform.ru/cms/quickform.css');

* The same but with browser condition

        // <!--[lte IE 7]><link rel="stylesheet" href="****.css" media="all" type="text/css" /><![endif]-->
        StaticCss::instance()->addCss('http://jquickform.ru/cms/quickform.css', 'lte IE 7');

* Adding virtual stylesheet file (will be searching in APPDIR.'static-files'.$file and MODDIR.$module.'static-files'.$file)

        StaticCss::instance()->addCssStatic('style.css');

* Inline styles adding

        StaticCss::instance()->addCssInline('.a:hover{color:red}');

* Adding real existing files of scripts on server or other host

        StaticJs::instance()->addJs('/js/pirobox.js');
        StaticJs::instance()->addJs('http://jquickform.ru/vendors/jQuickForm/quickform.js');

* Adding virtual javascript file

        StaticJs::instance()->addJsStatic('jquery/jquery-1.4.3.min.js');

* Inline scripts adding

        StaticJs::instance()->addJsInline('alert(\'test!\');');

* Adding scrripts that must be executed on page load

        StaticJs::instance()->addJsOnload(
            'jQuery(".del_link").click(
                function(){
                    alert("Mes-mes-messsssage!");
                }
            );',
            'qweq'
        );

* Also creates gzipped static file for some hostings who not allow compress static files using apache

## To load all added javascripts or scripts

        StaticJs::instance()->getJsAll();
        StaticCss::instance()->getCssAll();