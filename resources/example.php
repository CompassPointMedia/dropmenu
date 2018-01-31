<?php
/*

This page is entirely self-contained (as long as you have internet for the CDN resources).  It should work directly when you reference it or require it.

 *
 * This was developed with help from: https://codepen.io/ajaypatelaj/pen/prHjD
 * I've established a repeatable pattern (2nd, 3rd, etc menus), however the behavior needs improvement for mobile.
 * So here is what needs to be done:
 *  1. develop an array structure for the menu
 *      I am pretty well committed to the idea of a JSON-able array declaring the entire menu
 *      issues with lazy loading
 *      let's make this modular
 *      any menu should be able to export information about itself
 *      a menu should be able to store more information in its data-meta="" attribute
 *      if the format were consistent (<ul> has <li> has <a> and maybe sub <ul>) then I could read a menu elsewhere
 *          - even if I didn't know how it was generated
 *      all this would be a sweet package
 *
 *  2. currently a has-submenu link has a margin-right of 20px to indent the submenu - change this, use padding, and indent the submenu instead
 *  3. we need to change the following:
 *      fade transitions
 *      which arrow/icon
 *      have that arrow rotate
 *  4. work on responsive behavior for mobile - submenus
 *  5. behavior for popups needs to be different - all a.URLs need to be piped to a handler with class-based control (in turn coming fomr
 *
 * Without a doubt the nicest menu (so far) is the purple one at sharemylesson.com - very nice and follows the ul-li-a-ul recursion pattern.
 *
 *
 * Tested with 3.2.0 and 3.3.7 - fails in Bootstrap 4.0.0 css/js
 */

// this is what the menu array must look like
$menu = [
    'name' => 'Sample Menu',
    'config' => [],
    'children' => [
        [
            'label' => 'Grades',
            'title' => 'See lessons listed by grade groupings',
            'config' => [],
            'children' => [
                [
                    'label' => 'Preschool',
                    'title' => 'See lessons for preschool children',
                    'uri' => '/grades/preschool',
                ],
                [
                    'label' => 'Elementary (Grades K-2)',
                    'title' => 'See lessons for elementary grades K-2',
                    'uri' => '/grades/elementary-k-2',
                    'children' => [
                        [
                            'label' => 'Link 1 - Apples',
                            'title' => 'See information about apples',
                            'uri' => '/grades/elementary-k-2/apples',
                        ],
                        [
                            'label' => 'Link 2 - Bananas',
                            'title' => 'See information about bananas',
                            'uri' => '/grades/elementary-k-2/bananas',
                        ],
                        [
                            'label' => 'Cherries',
                            'title' => 'See information about cherries',
                            'uri' => '/grades/elementary-k-2/cherries',
                        ],
                    ],
                ],
                [
                    'label' => 'Elementary (Grades 3-5)',
                    'title' => 'See lessons for elementary grades 3-5',
                    'uri' => '/grades/elementary-3-5',
                ],
                [
                    'label' => 'Middle School',
                    'title' => 'See lessons for middle school/junior high students',
                    'uri' => '/grades/middle-school',
                ],
                [
                    'label' => 'High School',
                    'title' => 'See lessons for high school students',
                    'uri' => '/grades/high-school',
                ],
            ]
        ],
        [
            'label' => 'Standards',
            'uri' => '/standards',
            'title' => 'Standards Center',
        ],
    ]

];

// Another example
$menu = [
    'name' => 'Sample menu',
    'description' => 'Example menu with way too many sub-menus but it works',
    'children' => [
        [
            'label' => 'Home',
        ], [
            'label' => 'Menu 1',
            'children' => [
                [
                    'label' => 'Action [Menu 1.1]',
                ], [
                    'label' => 'Another action [Menu 1.1]',
                ], [
                    'label' => 'Something else here [Menu 1.1]',
                ], [
                    'divider' => true,
                ], [
                    'label' => 'Separated link [Menu 1.1]',
                ], [
                    'label' => 'One more separated link [Menu 1.1]',
                ], [
                    'label' => 'Dropdown [Menu 1.1]',
                    'children' => [
                        [
                            'label' => 'Action menu [Menu 1.2]',
                        ], [
                            'label' => 'Dropdown [Menu 1.2]',
                            'children' => [
                                [
                                    'label' => 'Dropdown [Menu 1.3]',
                                    'children' => [
                                        [
                                            'label' => 'Action [Menu 1.4]',
                                        ], [
                                            'label' => 'Another action [Menu 1.4]',
                                        ], [
                                            'label' => 'Something else here [Menu 1.4]',
                                        ], [
                                            'divider' => true,
                                        ], [
                                            'label' => 'Separated link [Menu 1.4]',
                                        ], [
                                            'divider' => true,
                                        ], [
                                            'label' => 'One more separated link [Menu 1.4]',
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
            ]
        ], [
            'label' => 'Menu 2',
            'children' => [
                [
                    'label' => 'Action [Menu 2.1]',
                ], [
                    'label' => 'Another action [Menu 2.1]',
                ], [
                    'label' => 'Something else here [Menu 2.1]',
                ]
            ]
        ]
    ]
];

// You can use multiple menus
$menu_github = [
    'ul_attributes' => [
        'class' => '+navbar-right',
    ],
    'children' => [
        [
            'label' => 'GitHub',
            'uri' => 'https://github.com/fontenele/bootstrap-navbar-dropdowns',
            'a_attributes' => [
                'target' => '_blank',
            ]
        ]
    ]
];

// this will translate the array into html output
function menu_generate_html($menu = [], $config = [], $level = 1){
/**
 * This function is the way that the output _should_ be in terms of html structure.
 */

extract($config);
$nl = "\n";
$readable = (!empty($readable));
$suppress = (!empty($suppress));
$suppressOpen = (!empty($suppressOpen));
$suppressClose = (!empty($suppressClose));

$html = '';

if(false){ // layout for readability
    ?>
    <ul>
        <li>
            <a href="/option-1"><!-- compact if no children -->
                                Option Alpha<!-- compact if no children -->
            </a>
            <!-- repeat @(level - 1) * 4 spaces -->
            <ul>
                <li>
                    <a>
                        Etc.
                    </a>
                </li>
            </ul>
        </li>
    </ul>
    <?php
}

// generate head tags
if($level === 1 && !($suppress || $suppressOpen)){
if($readable) $html = '<!-- generated by ' . __FUNCTION__ . ' at ' . date('F jS, g:iA') . ' -->' . $nl;
ob_start();
if(true) {
?>
<div class="navbar navbar-default navbar-fixed-top" role="navigation">
    <div class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="#">NavBar</a>
        </div>
        <div class="collapse navbar-collapse">
            <?php
            }
            $html .= ob_get_contents();
            ob_end_clean();
            if($readable) $html .= $nl;
            }

            // <ul>
            // we will always generate a ul container
            if($readable) $html .= '  ' . str_repeat('    ', $level - 1);
            $class = ($level === 1 ? 'nav navbar-nav' : 'dropdown-menu');
            if(!empty($menu['ul_attributes']['class'])){
                $add = $menu['ul_attributes']['class'];
                unset($menu['ul_attributes']['class']);
                if(substr($add,0,1) === '+'){
                    $class = trim($class) . ' ' . ltrim($add, '+');
                }else{
                    $class = $add;
                }
            }
            $class = ' class="'.$class.'"';
            $dataMenuLevel = ' data-menu-level="' . $level . '"';
            $html .= '<ul' . $class . $dataMenuLevel . '>';
            if($readable) $html .= $nl;

            if(!empty($menu['children'])){
                foreach($menu['children'] as $child){

                    if(!empty($child['divider'])){
                        // <li>
                        if($readable) $html .= '    ' . str_repeat('    ', $level - 1);
                        $html .= '<li class="divider"></li>';
                        if($readable) $html .= $nl;
                    }else{
                        // <li>
                        if($readable) $html .= '    ' . str_repeat('    ', $level - 1);
                        $class = '';
                        if(!empty($child['children']) && $level > 1){
                            $class .= ' dropdown-submenu';
                        }
                        if($class) $class = ' class="' . trim($class) . '"';
                        $html .= '<li' . $class .'>';
                        if($readable) $html .= $nl;

                        // <a>
                        $href = (!empty($child['uri']) ? ' href="' . $child['uri'] . '"' : ' href="#"');
                        $classDataToggle = (!empty($child['children']) ? ' class="dropdown-toggle" data-toggle="dropdown"' : '');
                        if($readable) $html .= '      ' . str_repeat('    ', $level - 1);
                        if(!empty($child['a_attributes'])) {
                            $a_attributes = '';
                            foreach($child['a_attributes'] as $n => $v){
                                $a_attributes .= ' '.$n.'="'.str_replace('"', '\"', $v) . '"';
                            }
                        }
                        $html .= '<a' . $href . $classDataToggle . $a_attributes . '>';
                        if($readable && !empty($child['children'])) $html .= $nl;

                        // content
                        if($readable && !empty($child['children'])) $html .= '        ' . str_repeat('    ', $level - 1);
                        if(empty($child['label'])){
                            // error but insert something
                            if(!empty($child['uri'])){
                                $a = preg_split('/[\\/]+/', $child['uri']);
                                $html .= $a[count($a) - 1];
                            } else {
                                $html .= '&nbsp;';
                            }
                        }else {
                            $html .= $child['label'];
                        }
                        // generate title and additional HTML after label
                        if(!empty($child['children'])) $html .= '<b class="caret"></b>';

                        if($readable && !empty($child['children'])) $html .= $nl;

                        // </a>
                        if($readable && !empty($child['children'])) $html .= '      ' . str_repeat('    ', $level - 1);
                        $html .= '</a>';
                        if($readable) $html .= $nl;


                        if(!empty($child['children'])){
                            // recursively call this function
                            $fn = __METHOD__;
                            $html .= $fn($child, $config, $level + 1);
                        }

                        // </li>
                        if($readable) $html .= '    ' . str_repeat('    ', $level - 1);
                        $html .= '</li>';
                        if($readable) $html .= $nl;
                    }
                }
            } else if($level === 1){
                $html .= '<li>';
                $html .= '<a>';
                $html .= '<!-- Menu is empty; please see documentation for more information -->' . "\n";
                $html .= 'Menu is empty'; //provide a link to assist in the documentation
                $html .= '</a></li>';
            }

            // </ul>
            if($readable) $html .= '  ' . str_repeat('    ', $level - 1);
            $html .= '</ul>';
            if($readable) $html .= $nl;

            // close head tags
            if($level === 1 && !($suppress || $suppressClose)){
            ob_start();
            ?>
        </div>
    </div>
</div>
<?php
$html .= ob_get_contents();
ob_end_clean();
}

return $html;
}

?><!DOCTYPE html>
<html lang="en" class=" -webkit-">
<head>

    <meta charset="UTF-8">
    <link rel='stylesheet prefetch' href='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css'>

    <style class="cp-pen-styles">
        .dropdown-submenu {
            position: relative;
        }

        .dropdown-submenu>.dropdown-menu {
            top: 0;
            left: 100%;
            margin-top: -6px;
            margin-left: -1px;
            -webkit-border-radius: 0 6px 6px 6px;
            -moz-border-radius: 0 6px 6px;
            border-radius: 0 6px 6px 6px;
        }

        .dropdown-submenu:hover>.dropdown-menu {
            display: block;
        }

        .dropdown-submenu>a:after {
            display: block;
            content: " ";
            float: right;
            width: 0;
            height: 0;
            border-color: transparent;
            border-style: solid;
            border-width: 5px 0 5px 5px;
            border-left-color: #ccc;
            margin-top: 5px;
            margin-right: -10px;
        }

        .dropdown-submenu:hover>a:after {
            border-left-color: #fff;
        }

        .dropdown-submenu.pull-left>.dropdown-menu {
            left: -100%;
            margin-left: 10px;
            -webkit-border-radius: 6px 0 6px 6px;
            -moz-border-radius: 6px 0 6px 6px;
            border-radius: 6px 0 6px 6px;
        }

        .navbar-collapse>.navbar-nav>li.open,
        .navbar-collapse>.navbar-nav>li.open>ul.dropdown-menu,
        .navbar-collapse>.navbar-nav>li:hover {
            background: #0C99BB;
        }

        .navbar-collapse>.navbar-nav>li {
            text-transform: uppercase;
            /*
            background: #fff;
            cursor: pointer;
            display: table-cell;
            text-align: center;
            /*transition: all .3s ease-in-out;*/
            transition-property: all;
            transition-duration: 0.3s;
            transition-timing-function: ease-in-out;
            transition-delay: initial;
            /*
            vertical-align: middle;
            width: 1%;
            */
        }
    </style>
</head>
<body>

<?php
// let's do this dynamically
echo menu_generate_html($menu_github, ['readable' => true, 'suppressClose' => true]);
echo menu_generate_html($menu, ['readable' => true, 'suppressOpen' => true]);
?>

<div class="container">
    <div class="navbar-template text-center">
        <br><br><br><br>
        <h1>Bootstrap NavBar</h1>
        <p class="lead text-info">NavBar with too many childs.</p>
        <a target="_blank" href="https://bootsnipp.com/snippets/featured/multi-level-dropdown-menu-bs3">Thanks to msurguy (Multi level dropdown menu BS3)</a>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
<script src='http://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js'></script>
<!--
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.3/js/bootstrap.min.js"></script>
-->

</body></html>