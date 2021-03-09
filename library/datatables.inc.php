<?php
/**
 * Functions for interacting with datatables calls.
 *
 * Copyright (C) 2015-2019 Daniel Pflieger <daniel@mi-squared.com> <daniel@growlingflea.com>
 *
 * LICENSE: This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 3 of the License, or (at your option) any
 * later version.  This program is distributed in the hope that it will be
 * useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General
 * Public License for more details.  You should have received a copy of the GNU
 * General Public License along with this program.
 * If not, see <http://opensource.org/licenses/gpl-license.php>.
 *
 * @author     Daniel Pflieger daniel@mi-squared.com>
 * @link       http://www.open-emr.org
 *
 * 2016-04-05 Changes made to keep this compliant with CAIR's requirement for TLS1.2
 * 2019-06-27 Changes made for 2 way communiation
 */

include_once("../globals.php");
require_once("$srcdir/sql.inc");
require_once("$srcdir/patient.inc");
require_once("$srcdir/formatting.inc.php");


class DATATABLES{

    //Takes in the columnms array from the datatable and creates a search function
    public static function returnWhereFromSearch($columns){
        $where = "";
        //for each column read the searchable value
        foreach($columns as $key=>$column){

            //if the seachable value is true read the search array
            if($column['searchable'] == "false") continue;
            if($column['search']['value'] == "") continue;
            //read the value stored in the search array and add it to the where statement, if the value is not equal to ""
            //this is where we build the where constraints.

            $where .= " {$column['data']} like '%{$column['search']['value']}%' AND ";


        }


        return substr($where, 0, -4);
    }


    public static function getColumnsForSelectStatement($columns){




    }



    //this get the last key where there is a value for searching
    private static function getLastKey($array){

        $key = NULL;

        if ( is_array( $array ) ) {

            end( $array );
            $key = key( $array );
        }

        return $key;
    }






}