<?php
// Requiring interface model for the class

class googleChartTools implements chartClass {
    public function loadValues($array);
    public function loadRequiredParameters($array);
    public function checkRequiredParameters($array);
    public function returnRequiredParameters();
    public function getHtml();
}