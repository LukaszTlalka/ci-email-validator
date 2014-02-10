<?
/**
 * This file holds array for which disposable email check is skipped. 
 * 
 * element key -> ignored
 * element value -> regular expression for domain to skip
 * 
 * example:
 * $_['google.*'] = '^google\.[a-zA-Z\.]{2,6}$';
 *
**/
$_['google.*'] = '/^google\.[a-zA-Z\.]{2,6}$/';
$_['gmail.*'] = '/^gmail\.[a-zA-Z\.]{2,6}$/';
