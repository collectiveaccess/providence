<?php

use PHPUnit\Framework\TestCase;

/**
 * ----------------------------------------------------------------------
 * DisableGzipTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2020 Whirl-i-Gig
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * This source code is free and modifiable under the terms of
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * @package CollectiveAccess
 * @subpackage tests
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 *
 */

class DisableGzipTest extends TestCase {
    /**
     * @var string
     */
    private $vs_controller;
    /**
     * @var string
     */
    private $vs_controller_with_gzip;
    /**
     * @var string
     */
    private $vs_action;
    /**
     * @var string
     */
    private $vs_action_enabled;
    /**
     * @var string
     */
    private $vs_controller_all_actions;


    protected function setUp(): void {
        parent::setUp();
        $this->vs_controller = 'TestDisableGzip';
        $this->vs_controller_list_actions = 'TestDisableGzipListActions';
        $this->vs_controller_all_actions = 'TestDisableGzipAllActions';
        $this->vs_controller_with_gzip = 'TestEditorWithGzip';
        $this->vs_action = 'TestAction';
        $this->vs_action_enabled = 'TestActionEnabled';
    }

    public function testDisableGzipForControllerAllActions(){
        // A controller with no actions will disable gzip all actions
        $result = caIsGzipDisabled($this->vs_controller_all_actions, null);
        $this->assertTrue($result);
    }
    public function testDisableGzipForControllerAllActionsWithAction(){
        $result = caIsGzipDisabled($this->vs_controller_all_actions, $this->vs_action);
        $this->assertTrue($result);
    }
    public function testDisableGzipForListOfActions(){
        $result = caIsGzipDisabled($this->vs_controller_list_actions, $this->vs_action);
        $this->assertTrue($result);
    }
    public function testDoNotDisableGzipNoAction(){
        $result = caIsGzipDisabled($this->vs_controller_with_gzip, null);
        $this->assertFalse($result);
    }
    public function testDoNotDisableGzipWithAction(){
        $result = caIsGzipDisabled($this->vs_controller_with_gzip, $this->vs_action_enabled);
        $this->assertFalse($result);
    }
    public function testDoNotDisableGzipControllerEmptyAction(){
        $result = caIsGzipDisabled($this->vs_controller, null);
        $this->assertFalse($result);
    }
    public function testDisableGzipControllerMatchingAction(){
        $result = caIsGzipDisabled($this->vs_controller, $this->vs_action);
        $this->assertTrue($result);
    }
    public function testDontDisabledGzipControllerActionNotFound(){
        $result = caIsGzipDisabled($this->vs_controller, 'NotFoundAction');
        $this->assertFalse($result);
    }
}