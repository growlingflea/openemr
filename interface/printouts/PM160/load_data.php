<?php
function create_row(DOMDocument $DOM,$label,$value)
{
    $retval=$DOM->createElement("tr");
    $tdLabel=$DOM->createElement("td",$label);
    $tdValue=$DOM->createElement("td",$value);
    
    $retval->appendChild($tdLabel);
    $retval->appendChild($tdValue);    
    return $retval;
}

function appointment_info(DOMDocument $DOM, DOMElement $parent,$pid)
{
      $apptQuery = "SELECT e.pc_eid, e.pc_aid, e.pc_title, e.pc_eventDate, " .
      "e.pc_startTime, e.pc_hometext, u.fname, u.lname, u.mname, " .
      "c.pc_catname, e.pc_apptstatus " .
      "FROM openemr_postcalendar_events AS e, users AS u, " .
      "openemr_postcalendar_categories AS c WHERE " .
      "e.pc_pid = ? AND e.pc_eventDate > CURRENT_DATE AND " .
      "u.id = e.pc_aid AND e.pc_catid = c.pc_catid " .
      " AND NOT (pc_apptstatus IN ('x','%')) ".
      "ORDER BY e.pc_eventDate, e.pc_startTime LIMIT 1";
     $appointment = sqlQuery($apptQuery, array($pid) );
     if($appointment!=false)
     {
        $dispampm = "am";
        $disphour = substr($appointment['pc_startTime'], 0, 2) + 0;
        $dispmin  = substr($appointment['pc_startTime'], 3, 2);
        if ($disphour >= 12) {
            $dispampm = "pm";
            if ($disphour > 12) $disphour -= 12;
        }
        $date_parts=explode("-",$appointment['pc_eventDate']);
        $apptDate=$date_parts[1]."-".$date_parts[2]."-".$date_parts[0];
        $apptTime=$disphour.":".$dispmin." ".$dispampm;
        $retval=create_row($DOM,"Next Appointment",$apptDate." ".$apptTime);
        $parent->appendChild($retval);
        return $retval;
     }
}

function stature_info(DOMDocument $DOM, DOMElement $parent,$pid)
{
    $sqlVitals = "SELECT height,weight,BMI, head_circ, date FROM form_vitals WHERE pid=? ORDER BY date desc LIMIT 1";
    $vitals_data = sqlQuery($sqlVitals,array($pid));
    if($vitals_data['weight']!=0) 
    {
       $pounds_int=floor($vitals_data['weight']);            
       $parent->appendChild(create_row($DOM,"Weight",sprintf("%dlb %doz",$pounds_int,($vitals_data['weight']-$pounds_int)*16)));             
    }
    if($vitals_data['height']!=0) 
        $parent->appendChild(create_row($DOM,"Height",$vitals_data['height']." in"));
    if($vitals_data['BMI']!=0)     
        $parent->appendChild(create_row($DOM,"BMI",$vitals_data['BMI']));
    
    //CUSTOM CHANGE START
    
    //
    //Head Circumference START    
    //This is the function that prints head_cirm to the screen
    if($vitals_data['head_circum']!=0.00){
        //if the last encounter a head circumference was taken, print it to the screen with the date it was taken
        $outputString = floatval($vitals_data['head_circum'])." measured on ".$vitals_data['date'];
        $parent->appendChild(create_row($DOM, "Head Circum", $outputString));
    }else {        
        //else, search for the last time the head circum was taken, print the date and measurement
        $sqlLast_head_circ = "SELECT head_circ, date FROM form_vitals WHERE pid=? AND head_circ != 0.00 ORDER BY date desc";
        $sqlLast_head_circData = sqlQuery($sqlLast_head_circ, array($pid));
        $vitals_data['head_circum'] = $sqlLast_head_circData['head_circ'];
        if ($vitals_data['head_circum']!=0){
        $outputString = floatval($sqlLast_head_circData['head_circ'])." measured on ".$sqlLast_head_circData['date'];
        } else $outputString = "No measurement recorded";
        $parent->appendChild(create_row($DOM, "Head Circum", $outputString));        
    }
    //Head Circumference END
    
    
    //Lead Start
    //Get last lead result, ordered by date desc
    $sqlLead = "Select * from forms JOIN lbf_data on forms.form_id=lbf_data.form_id WHERE pid=? ".
                "AND form_name LIKE '%lead%' ".
                "AND field_id LIKE '%lead%' ".
                "ORDER BY date DESC ".
                "LIMIT 1;";
    
    // 'sql_data['field_value'] holds the lead field value
    // 'sql data['field_id'] needs to have value of lead
    $sqlLead_data = sqlQuery($sqlLead, array($pid)); 
    if($sqlLead_data!=0){
        $outputString = $sqlLead_data['field_value']." Taken on ".$sqlLead_data['date'];
        $vitals_data['lead_value'] = $sqlLead_data['field_value'];
    }
        else $outputString ="No available measurements";     
    $parent->appendChild(create_row($DOM, "Lead Measurement: ", $outputString));
    //Lead End  
    
   
    //Vaccination START***
    //Prints Vaccinations to the PM160 Screen 
    $sqlVaccine = "SELECT administered_date, patient_id, code_text_short ".
                    "FROM codes ".
                    "INNER JOIN immunizations ON codes.code = Cast( immunizations.cvx_code AS char ) ".
                    "WHERE immunizations.patient_id = ? ".
                    "ORDER BY immunizations.administered_date ";
   
    $sqlVaccineData = sqlStatement($sqlVaccine, array($pid));
    while ($row = sqlFetchArray($sqlVaccineData))                
        {
          $outputString = $row['administered_date']." ".$row['code_text_short'];
          $parent->appendChild(create_row($DOM, "Immunization: ", $outputString));
        }    
    //Vaccination Ends  
        
    //Tobacco START***
    //Prints tobacco passive/active uses to PM160 screen
    $sqlTobacco = "SELECT fm.date, fm.encounter, fm.form_name, fm.form_id, fm.pid, lbf.form_id, lbf.field_id, lbf.field_value, deleted ".
                    "FROM forms AS fm ".
                    "JOIN lbf_data as lbf on fm.form_id = lbf.form_id ".
                    "where fm.pid =? ".                   
                    "AND deleted = 0 AND(( lbf.field_id like 'TobaccoCounselRefer%' OR lbf.field_id = 'PatientTobacco' OR lbf.field_id = 'PassiveSmoke' )) " .
                    "Order by fm.date Desc";
    
    $sqlTobaccoData = sqlStatement($sqlTobacco, array($pid));
    $passiveFlag = 0;
    $patientTFlag =0;
    $tobaccoCoFlag = 0;
     while ($row = sqlFetchArray($sqlTobaccoData))                
        {
          if($row['field_id']==='PassiveSmoke' && $passiveFlag === 0){  
            $vitals_data['PassiveSmoke'] = $row['field_value'];
            $outputString = $row['date']." ".$row['field_value'];
            $parent->appendChild(create_row($DOM, "Exposed to 2nd Hand Smoke: ", $outputString));
            $passiveFlag = 1;
          }          
          if($row['field_id']==='PatientTobacco' && $patientTFlag === 0){
            $vitals_data['PatientTobacco'] = $row['field_value'];
            $outputString = $row['date']." ".$row['field_value'];
            $parent->appendChild(create_row($DOM, "Patient Uses Tobacco?: ", $outputString));
            $patientTFlag = 1;
          }            
          if($row['field_id']==='TobaccoCounselRefer' && $tobaccoCoFlag === 0){
            $vitals_data['TobaccoCounselRefer'] = $row['field_value'];
            $outputStringTCR = $row['date']." ".$row['field_value'];
            $parent->appendChild(create_row($DOM, "Counseled on Tobacco?: ", $outputStringTCR));
            $tobaccoCoFlag = 1;
          }  
        }             
    //Tobacco END
        
        
    //Dentist START***    
        $sqlDentist = "SELECT fm.date, fm.encounter, fm.form_name, fm.form_id, fm.pid, lbf.form_id, lbf.field_id, lbf.field_value, deleted ".
                    "FROM forms AS fm ".
                    "JOIN lbf_data as lbf on fm.form_id = lbf.form_id ".
                    "where fm.pid =? ".
                    "AND fm.form_name LIKE '%Antic%' ".
                    "AND deleted = 0 AND lbf.field_id = 'anticipDental' " .
                    "Limit 1";
    
        $sqlDentistdata = sqlStatement($sqlDentist, array($pid));
        while ($row = sqlFetchArray($sqlDentistdata)) {
             if($row['field_value']){
              $vitals_data['DentistReferred']=$row['field_value'];
              $parent->appendChild(create_row($DOM, "Dental Referral on: ", $row['date']));          
                 
             }           
        
        
        }        
    //Dentist END
    //https://ww3.iehp.org/en/providers/forms/pm160-forms/~/media/Provider%20Services/Forms/PM160%20Forms/PM160_form.pdf    
          
     if($vitals_data!==false)
     {
         if($vitals_data['height']!=0) $patient_info['height']=$vitals_data['height']." in";
         if($vitals_data['BMI']!=0) $patient_info['bmi']=$vitals_data['BMI'];
         if($vitals_data['head_circum']!=0) $patient_info['head_circum']=$vitals_data['head_circum'];
         if($vitals_data['lead_value']!= 0) $patient_info['lead_value']=$vitals_data['lead_value'];
         if($vitals_data['PassiveSmoke']!=0)$patient_info['PassiveSmoke']=$vitals_data['PassiveSmoke'];
         if($vitals_data['PatientTobacco']!=0)$patient_info['PatientTobacco']=$vitals_data['PatientTobacco'];
         if($vitals_data['TobaccoCounselRefer']!=0)$patient_info['TobaccoConselRefer']= $vitals_data['TobaccoCounselRefer'];
         
     }
}
?>
