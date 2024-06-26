<?php
namespace Reportico\Engine;
// -----------------------------------------------------------------------------
// -- Reportico -----------------------------------------------------------------
// -----------------------------------------------------------------------------
// Module : config.php
//
// General User Configuration Settings for Reportico Operation
// -----------------------------------------------------------------------------

// Password required to gain access to Administration Panel
// Set to Blank to allow reset from browser
ReporticoApp::setConfig('admin_password','peeksafety');

ReporticoApp::setConfig('http_basedir', './');
ReporticoApp::setConfig('http_urlhost', 'http://127.0.0.1');


// Default System Language
ReporticoApp::setConfig('language','en_gb');

ReporticoApp::setConfig('default_project', 'reports');

// Project Title used at the top of menus
ReporticoApp::setConfig('project_title','PeekTrack Reporting');

// Identify whether to always run in into Debug Mode
ReporticoApp::setConfig('allow_output', false);
ReporticoApp::setConfig('allow_debug', true);

// Identify whether Show Criteria is default option
ReporticoApp::setConfig('default_showcriteria', false);

// Specification of Safe Mode. Turn on SAFE mode by specifying true.
// In SAFE mode, design of reports is allowed but Code and SQL Injection
// are prevented. This means that the designer prevents entry of potentially
// cdangerous ustom PHP source in the Custom Source Section or potentially
// dangerous SQL statements in Pre-Execute Criteria sections
ReporticoApp::setConfig('safe_design_mode', true);

// If false prevents any designing of reports
ReporticoApp::setConfig('allow_maintain',true);

// If false prevents any designing of reports
ReporticoApp::setConfig('allow_maintain', true);

// DB connection details for ADODB
ReporticoApp::setConfig('db_driver', 'none');
ReporticoApp::setConfig('db_user', '');
ReporticoApp::setConfig('db_password', '');
ReporticoApp::setConfig('db_host', 'localhost');
ReporticoApp::setConfig('db_database', '');
ReporticoApp::setConfig('db_connect_from_config', true);
ReporticoApp::setConfig('db_dateformat', 'Y-m-d');
ReporticoApp::setConfig('prep_dateformat', 'Y-m-d');
ReporticoApp::setConfig('db_server', '');
ReporticoApp::setConfig('db_protocol', '');
ReporticoApp::setConfig('db_encoding', 'None');

//HTML Output Encoding
ReporticoApp::setConfig('output_encoding', 'UTF8');

// Identify temp area
ReporticoApp::setConfig('tmp_dir', "tmp");

// Parameter Defaults
ReporticoApp::setConfig('DEFAULT_PageSize', 'A4');
ReporticoApp::setConfig('DEFAULT_BottomMargin', "2cm");
ReporticoApp::setConfig('DEFAULT_LeftMargin', "1cm");
ReporticoApp::setConfig('DEFAULT_RightMargin', "1cm");
ReporticoApp::setConfig('DEFAULT_pdfFont', "Helvetica");
ReporticoApp::setConfig('DEFAULT_pdfFontSize', "10");

// FPDF parameters
ReporticoApp::setConfig('fpdf_fontpath', "./fpdf/font/");

// Graph Defaults
ReporticoApp::setConfig('DEFAULT_GraphWidth', 800);
ReporticoApp::setConfig('DEFAULT_GraphHeight', 400);
ReporticoApp::setConfig('DEFAULT_GraphWidthPDF', 500);
ReporticoApp::setConfig('DEFAULT_GraphHeightPDF', 250);
ReporticoApp::setConfig('DEFAULT_GraphColor', "yellow");
ReporticoApp::setConfig('DEFAULT_MarginTop', "20");
ReporticoApp::setConfig('DEFAULT_MarginBottom', "80");
ReporticoApp::setConfig('DEFAULT_MarginLeft', "50");
ReporticoApp::setConfig('DEFAULT_MarginRight', "50");
ReporticoApp::setConfig('DEFAULT_MarginColor', "red");
ReporticoApp::setConfig('DEFAULT_XTickLabelInterval', "4");
ReporticoApp::setConfig('DEFAULT_YTickLabelInterval', "2");
ReporticoApp::setConfig('DEFAULT_XTickInterval', "1");
ReporticoApp::setConfig('DEFAULT_YTickInterval', "1");
ReporticoApp::setConfig('DEFAULT_GridPosition', "back");
ReporticoApp::setConfig('DEFAULT_XGridDisplay', "none");
ReporticoApp::setConfig('DEFAULT_XGridColor', "gray");
ReporticoApp::setConfig('DEFAULT_YGridDisplay', "major");
ReporticoApp::setConfig('DEFAULT_YGridColor', "gray");
ReporticoApp::setConfig('DEFAULT_TitleFont', "Font2");
ReporticoApp::setConfig('DEFAULT_TitleFontStyle', "Normal");
ReporticoApp::setConfig('DEFAULT_TitleFontSize', "12");
ReporticoApp::setConfig('DEFAULT_TitleColor', "black");
ReporticoApp::setConfig('DEFAULT_XTitleFont', "Font1");
ReporticoApp::setConfig('DEFAULT_XTitleFontStyle', "Normal");
ReporticoApp::setConfig('DEFAULT_XTitleFontSize', "12");
ReporticoApp::setConfig('DEFAULT_XTitleColor', "black");
ReporticoApp::setConfig('DEFAULT_YTitleFont', "Font1");
ReporticoApp::setConfig('DEFAULT_YTitleFontStyle', "Normal");
ReporticoApp::setConfig('DEFAULT_YTitleFontSize', "12");
ReporticoApp::setConfig('DEFAULT_YTitleColor', "black");
ReporticoApp::setConfig('DEFAULT_XAxisFont', "Font1");
ReporticoApp::setConfig('DEFAULT_XAxisFontStyle', "Normal");
ReporticoApp::setConfig('DEFAULT_XAxisFontSize', "12");
ReporticoApp::setConfig('DEFAULT_XAxisFontColor', "black");
ReporticoApp::setConfig('DEFAULT_XAxisColor', "black");
ReporticoApp::setConfig('DEFAULT_YAxisFont', "Font1");
ReporticoApp::setConfig('DEFAULT_YAxisFontStyle', "Normal");
ReporticoApp::setConfig('DEFAULT_YAxisFontSize', "12");
ReporticoApp::setConfig('DEFAULT_YAxisFontColor', "black");
ReporticoApp::setConfig('DEFAULT_YAxisColor', "black");

// Statis Menu Title default to project Title
ReporticoApp::set('menu_title',ReporticoApp::getConfig('project_title'));

// Vertical centre page main menu, default show all reports in project
ReporticoApp::set('static_menu', array (
        array ( "language" => "en_gb", "report" => "", "title" => "BLANKLINE" ),
	    array ( "language" => "en_gb", "report" => "createproject.xml", "title" => "Create A New Project" ),
	    array ( "language" => "en_gb", "report" => "createtutorials.xml", "title" => "Configure Tutorials" ),
	));

// Vertical centre page main menu in admin mode default to all reports
ReporticoApp::set('admin_menu', array (
        array ( 'language' => 'en_gb', 'report' => '.*\.xml', 'title' => '<AUTO>' )
        ));

// Dropdown project menu, default to none
ReporticoApp::set('dropdown_menu', false );

?>
