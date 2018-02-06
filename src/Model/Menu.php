<?php

namespace Compasspointmedia\Julietmenu\Model;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model {
	//
	protected $table = "menus";

	public function __construct($arguments, $options) {
        parent::__construct();
        $this->arguments = $arguments;
        $this->options = $options;
    }


    public function node(){
        // We are setting the node into a group
        $arguments = $this->arguments;
        $options = $this->options;

    }
}

