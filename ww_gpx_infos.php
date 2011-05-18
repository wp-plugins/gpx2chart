<?php
// vim: set ts=4 et nu ai syntax=php indentexpr= :vim
/*
Plugin Name: gpx2chart
Plugin URI: http://wordpress.org/extend/plugins/gpx2chart/
Description: gpx2chart - a WP-Plugin for extracting some nice graphs from GPX-Files
Version: 0.1.2
Author: Walter Werther
Author URI: http://wwerther.de/
Update Server: http://downloads.wordpress.org/plugin
Min WP Version: 3.1.2
Max WP Version: 3.1.2
 */


require_once(dirname(__FILE__).'/ww_gpx.php');
define('GPX2CHART_SHORTCODE','gpx2chart');

class GPX2CHART {

    static $container_name='GPX2CHART_CONTAINER';

	static $add_script;
    static $foot_script_content='';
 
	function init() {
		add_shortcode(GPX2CHART_SHORTCODE, array(__CLASS__, 'handle_shortcode'));

        self::$add_script=0;
        self::$foot_script_content='<script type="text/javascript">$=jQuery;';

        wp_register_script('highcharts', plugins_url().'/ww_gpx_infos'.'/js/highcharts.js', array('jquery'), '2.1.4', false);
		wp_register_script('highchartsexport', plugins_url().'/ww_gpx_infos'.'/js/modules/exporting.js', array('jquery','highcharts'), '2.1.4', false);

        add_action('wp_footer', array(__CLASS__, 'add_script'));
	}

    function create_series($seriesname,$seriescolor,$seriesaxis,$series_data_name,$dashstyle=null) {
        if (!is_null($dashstyle)){
            $dashstyle="dashStyle: '$dashstyle',";               
        } else $dashstyle='';

        return "
            {
             name: '$seriesname',
             color: '$seriescolor',
             yAxis: $seriesaxis,
             $dashstyle
             marker: {
                enabled: false
             },
             type: 'spline',
             data: $series_data_name
          }
        ";

    }

    function create_axis($axistitle,$axiscolor,$leftside=true,$axisno=0,$formatter=null) {
        $opposite='false';
        if ($leftside==false) $opposite='true';

        if (!is_null($formatter)){
            $formatter="
            formatter: function() {
               $formatter
            },
            ";
        } else $formatter='';

        return "
          { // Another Y-Axis No: $axisno
             labels: {
                $formatter
                style: {
                color: '$axiscolor'
            }
         },
         title: {
            text: '$axistitle',
            style: {
               color: '$axiscolor'
            }
         },
         opposite: $opposite
      }
      ";
    }


	public static function formattime($value) {
            return strftime('%H:%M:%S',$value);
	}

/*
 * Our shortcode-Handler for GPX-Files
 * It provides support for the necessary parameters that are defined in
 * http://codex.wordpress.org/Shortcode_API
 */
	function handle_shortcode( $atts, $content=null, $code="" ) {
        // $atts    ::= array of attributes
        // $content ::= text within enclosing form of shortcode element
        // $code    ::= the shortcode found, when == callback name
        // examples: [my-shortcode]
        //           [my-shortcode/]
        //           [my-shortcode foo='bar']
        //           [my-shortcode foo='bar'/]
        //           [my-shortcode]content[/my-shortcode]
        //           [my-shortcode foo='bar']content[/my-shortcode]
        //           [wwgpxinfo href="<GPX-Source>" (maxelem="51")     ]
    	self::$add_script++;

        $divno=self::$add_script;

        $error=0;
        $container=self::$container_name.$divno;
        $postcontent='';
        $directcontent='';


        $directcontent.="<!-- ATTRIBUTES:\n".var_export ($atts,true)."\n -->\n";


        $directcontent.='<div id="'.$container.'" style="width:90%;">'."\n";

        /*
         * Evaluate mandatory attributes
         */
        if (! array_key_exists('href',$atts)) {
            $directcontent.="Attribute HREF is missing<br/>";
            $error++;
        }

        /* In Case of errors we abort here*/
        if ($error>0) return $directcontent."</div>";


        /* 
         * Evaluate optional attributes 
         */

        $maxelem=51;
        if (array_key_exists('maxelem',$atts)) {
            $maxelem=intval($atts['maxelem']);
        }

        
        # Read in the GPX-File
        $gpx=new WW_GPX($atts['href']);
    
        if (! $gpx->parse() ) {
            /* In Case of errors we abort here*/
            $directcontent."Error parsing GPX-File</div>";
        };

        # $directcontent.="<!-- GPX-Dump-Information -->\n".$gpx->dump()."\n";

        $colors['heartrate']='#AA4643';
        $colors['cadence']='#4572A7';
        $colors['elevation']='#89A54E';
        $colors['speed']='#CACA00';
   
        $axistitle['heartrate']='Heartrate (bpm)';
        $axistitle['cadence']='Cadence (rpm)';
        $axistitle['elevation']='Elevation (m)';
        $axistitle['speed']='Speed (km/h)';

        $axisleft['heartrate']=true;
        $axisleft['cadence']=true;
        $axisleft['elevation']=false;
        $axisleft['speed']=false;

        $jsvar['heartrate']="data[$divno]['hrs']";
        $jsvar['cadence']="data[$divno]['cadence']";
        $jsvar['elevation']="data[$divno]['elevation']";
        $jsvar['speed']="data[$divno]['speed']";
        $jsvar['xAxis']="data[$divno]['xAxis']";
        $jsvar['totaldistance']="data[$divno]['totaldistance']";
        $jsvar['totalinterval']="data[$divno]['totalinterval']";
        $jsvar['lat']="data[$divno]['lat']";
        $jsvar['lon']="data[$divno]['lon']";

        $seriesname['heartrate']='Heartrate';
        $seriesname['cadence']='Cadence';
        $seriesname['elevation']='Elevation';
        $seriesname['speed']='Speed';

        $seriesunit['heartrate']='bpm';
        $seriesunit['cadence']='rpm';
        $seriesunit['elevation']='m';
        $seriesunit['speed']='km/h';


        $params=array('heartrate','cadence','elevation','speed');
        foreach ($params as $param) {
            $axistitle[$param]=$atts['title_'.$param] ? $atts['title_'.$param] : $axistitle[$param];
            $colors[$param]=$atts['color_'.$param] ? $atts['color_'.$param] : $colors[$param];
        }

        $dashstyle['heartrate']='shortdot';

        $enableexport='false';

        # The maximum series that are available
        $process=array('heartrate','cadence','elevation','speed');

        # If we have defined a display variable we intersect the two arrays and take only the ones that are in both
        $process=$atts['display'] ? array_intersect($process,split(' ',$atts['display'])) : $process;

        # We remove the entries where we don't have data in our GPX-File
        if (! $gpx->meta->heartrate ) $process=array_diff($process,array('heartrate')); # Remove heartrate graph if we don't have any Meta-information about heartbeats
        if (! $gpx->meta->cadence ) $process=array_diff($process,array('cadence')); # Remove cadence graph if we don't have ayn Meta-information about cadence

        $title = $gpx->meta->name;
        $subtitle=strftime('%d.%m.%Y %H:%M',$gpx[0]['time'])."-".strftime('%d.%m.%Y %H:%M',$gpx[-1]['time']);

        $directcontent.='<script type="text/javascript">'."
           if (! data) {
               var data=new Array();
           }
           data[$divno]=new Array();
        ";
        #  $directcontent.=$jsvar['xAxis']."= new Array('".join("','",$time)."');\n";
        #
        $gpx->setmaxelem($maxelem);
        foreach ($process as $elem) {
           $directcontent.=$jsvar[$elem]."= new Array(".join(",",$gpx->return_pair($elem) ).");\n";
        }
        $directcontent.=$jsvar['totaldistance']."={".join(",",$gpx->return_assoc('totaldistance'))."};\n";
        $directcontent.=$jsvar['totalinterval']."={".join(",",$gpx->return_assoc('totalinterval') )."};\n";

        $directcontent.=$jsvar['lat']."={".join(",",$gpx->return_assoc('lat') )."};\n";
        $directcontent.=$jsvar['lon']."={".join(",",$gpx->return_assoc('lon') )."};\n";

        $directcontent.="</script>\n";

        $metadata="Spd: ".$gpx->averagespeed()."km/h HR: ".$gpx->averageheartrate()."bpm Total: ".$gpx->totaldistance()." km";

        $directcontent.=$gpx->dump();

        $directcontent.=<<<EOT
            <div id="${container}chart"></div>
            <div id="${container}meta">
            $metadata
            </div>
            <div id="${container}debug"> </div>
        </div>
EOT;

        $yaxis=array();
        $series=array();
        $series_units=array();

        $axisno=0;
        foreach ($process as $elem) {
            array_push($yaxis,self::create_axis($axistitle[$elem],$colors[$elem],$axisleft[$elem],$axisno));
            array_push($series,self::create_series($seriesname[$elem],$colors[$elem],$axisno,$jsvar[$elem],$dashstyle[$elem]));
            array_push($series_units,"'".$seriesname[$elem]."':'".$seriesunit[$elem]."'");
            $axisno++;
        }

        $series_units = join (',',$series_units);
#categories: $jsvar[xAxis],
        $postcontent.=<<<EOT
var \$${container}debug = $('#${container}debug');

chart$divno = new Highcharts.Chart({
      chart: {
         renderTo: '${container}chart',
         zoomType: 'x'
      },
      title: {
         text: '$title'
      },
      subtitle: {
         text: '$subtitle'
      },
      xAxis: [{
         type: 'datetime',
         labels: {
            formatter: function() {
                return Highcharts.dateFormat('%d.%m %H:%M:%S', this.value);
            },
            rotation: 90,
            align: 'left',
            showFirstLabel: true,
            showLastLabel: true
         }
      }],
      yAxis: [
EOT;

$postcontent.=join(',',$yaxis);

$postcontent.=<<<EOT
      ],
      tooltip: {
         shared: true,
         crosshairs: true,
         borderColor: '#CDCDCD',
         formatter: function() {
            var s = '<b>'+ Highcharts.dateFormat('%d.%m.%Y %H:%M:%S', this.x) +'</b>';
            $.each(this.points, function(i, point) {
                var unit = { $series_units } [point.series.name];
                s += '<br/><span style="font-weight:bold;color:'+point.series.color+'">'+ point.series.name+':</span>'+ Math.round(point.y*100)/100 +' '+ unit+'';
            });
            s+= '<br/><span style="font-weight:bold;">Strecke:</span></td><td>'+Math.round($jsvar[totaldistance][this.x]/1000*100)/100+' km';
            s+= '<br/><span style="font-weight:bold;">Zeit:</span></td><td>'+Math.floor($jsvar[totalinterval][this.x]/3600)+':'+Math.floor($jsvar[totalinterval][this.x]/60)%60+':'+$jsvar[totalinterval][this.x]%60+' h';
            return s;
          }
      },
      plotOptions: {
         area: {
            fillOpacity: 0.5
         },
         series: {
            point: {
                events: {
                    mouseOver: function() {
                        var lat=$jsvar[lat][this.x];
                        var lon=$jsvar[lon][this.x];
                        \$${container}debug.html('Lat: '+ lat +', Lon: '+ lon);
/*
                        markers=map.getLayersByName('Marker')[0];
                        var ll = new OpenLayers.LonLat(lon,lat).transform(map.displayProjection, map.projection);
                        markers.clearMarkers();
                        var size = new OpenLayers.Size(21,25);
                        var offset = new OpenLayers.Pixel(-(size.w/2), -size.h);
                        var icon = new OpenLayers.Icon('http://wwerther.de/wp-content/plugins/osm/icons/marker_blue.png', size, offset);
                        markers.addMarker(new OpenLayers.Marker(ll,icon));
*/
                    }
                }
            },
            events: {
                mouseOut: function() {                        
                    \$reporting.empty();
                }
            }
        }
      },
      legend: {
         layout: 'horizontal',
         align: 'center',
         verticalAlign: 'bottom',
         floating: false,
         backgroundColor: '#FFFFFF'
      },
      series: [
EOT;

$postcontent.=join(',',$series);

$postcontent.=<<<EOT
        ],
      exporting: {
        enabled: $enableexport,
        filename: 'custom-file-name'
      }
   });
EOT;

    self::$foot_script_content.=$postcontent;
    return $directcontent;

    }
 
	function add_script() {
        if (self::$add_script>0) {
            wp_print_scripts('highcharts');
        	wp_print_scripts('highchartsexport');

            print self::$foot_script_content;
            print "</script>";
        }
	}
}
 
/*
 * I just define a small test-scenario, wether or not the add_shortcode function 
 * already exists. This allows me to do a compilation test of this file
 * without the full overhead of wordpress
 * This is used when I do a git commit to guarantee, that the code will compile
 * properly
 */
if (! function_exists('add_shortcode')) {
        function wp_register_script() {
        }
        function plugins_url() {
        }
        function add_action() {
        }
        function wp_print_scripts() {
        }
        function add_shortcode ($shortcode,$function) {
                echo "Only Test-Case: $shortcode: $function";

                print GPX2CHART::handle_shortcode(array('href'=>'http://sonne/cadence.gpx','maxelem'=>0),null,'');
                print GPX2CHART::add_script();
        };
}


GPX2CHART::init();

?>
