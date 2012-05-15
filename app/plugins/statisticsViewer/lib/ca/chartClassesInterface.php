<?php
// Declaration de l'interface 'iTemplate'
interface chartClass
{
    public function loadValues($array);
    public function loadRequiredParameters($array);
    public function checkRequiredParameters($array);
    public function returnRequiredParameters();
    public function getHtml();
}
