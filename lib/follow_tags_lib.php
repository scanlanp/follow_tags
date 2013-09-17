<?php
/**
 *  Follow Tag Libary
 *
 */


function existFollowTagObject($guid){
  $num_tags = elgg_get_entities(array('type' => 'object' ,
                  'subtype' => 'FollowTags',
                  'owner_guid' => $guid,
                  'count'  => true,              
                  ));
  //Return true if there is only one Object for each User else return false
  if($num_tags == 1){ return true; }else{ return false;}
}


function createFollowTagObject(){
  $user = elgg_get_logged_in_user_entity();
  
  $followTag = new ElggObject();
  $followTag->subtype = "FollowTags";
  $followTag->owner_guid = elgg_get_logged_in_user_guid();
  $followTag->title = $user->name;
  $followTag->access_id = 1;
  $followTag->description = "";

  //Notify standard value 
  $followTag->notify = "on";
  
  if (!$followTag->save()) {
    register_error(elgg_echo("followTags:save:error"));
    forward(REFERER);
  }

  return true;
}

function saveFollowTags($input,$id,$notify){
  //Get FollowTagObject and Clear all Tag Relationships
  $user = elgg_get_logged_in_user_entity();
  
  $followTags = get_entity($id);
  $followTags->clearRelationships();
  $followTags->description =$input;
  $followTags->title = $user->name;
  $followTags->access_id = 1;
  $followTags->notify = $notify;

  //Convert the Taginput string to array and save to FollowTagObj
  $tagarray = string_to_tag_array($input);
  $followTags->tags = $tagarray;
  
  $saved =$followTags->save();
 
  if(!$saved){
      return false;
  }
    return true;
}


function getID($guid){
    $options = array('type' => 'object' ,
                  'subtype' => 'FollowTags',
                  'owner_guid' => $guid,    
                                             );
    $tags = elgg_get_entities($options);

    foreach($tags as $tag) {
        $guid = $tag->getGUID();
    }
    //Return the GUID of the FollowTagsObj
    return $guid;
}

function getCurrentTagsFrom($guid){
    $options = array('type' => 'object' ,
                  'subtype' => 'FollowTags',
                  'owner_guid' => $guid,    
                                             );
    $tags = elgg_get_entities($options);

    foreach($tags as $tag) {
        $value = $tag->description;
    }
    //return the current following tags
    return $value;
}

  //Return the Notificationsettings for FollowTags
function getNotificationSettings($guid){
    
    $follow_tag_notify = get_entity($guid);
    $notify_value = $follow_tag_notify->notify;

  if($notify_value == "on")
  {
    return true;
  }
  
    return false;
}




function followtags_notify($event, $type, $object) {
    
//subtype 11,9,7,6 are notification, message, FollowTags, admin_notice
//followtags_notify should not run if the create object have one of this subtypes
 $sub = $object->subtype;
//Dont notifyuser if object is private	
 $access = $object->access_id; // 0 is private


if($access != '0')
{ 
	    //Get all tags from created Object
    $tags = get_metadata_byname ($object->guid,'tags');

    //Check the number of tags and handle 0 and 1 tags
    switch (count($tags)) {
      case 0:
          return; //
      break;

      case 1:
        $tagid = $tags['value'];
        
      break;
    
      default:
        foreach ($tags as $tag) {
           $tagid .= $tag['value'];
            $tagid .= ",";   
        }
     break;
     }
	
    //Create Tagarray 
    $tagarray = explode(",",$tagid);

    //Compare object tags with all FollowTagsObject  
    $users = elgg_get_entities_from_metadata(array(
        'type' => 'object' ,
        'subtype' => 'FollowTags',
        'metadata_values' => $tagarray,
        
         
    )); 
 	
 	
 	

 	   
    //Check how many user follow object tags and create a acceptor array
	
    if(count($users) == 1){

     // Only one user 
    
     $follow_tag_notify = get_entity($users[0][guid]);
     $notify_value = $follow_tag_notify->notify;
    
     if($notify_value == "on"){     
      
     $to = $users[0]['owner_guid'];
     }else{
        return;
     }
     

     
     if($to == get_loggedin_user()->guid){
      //Dont notify creator
       return;
     }
  
  }else{

    //More than 1 user
    foreach ($users as $user) {
      
      //Get guid from following user
      if($user->owner_guid != get_loggedin_user()->guid){

        //Create a string with all users
      if($user->notify == "on"){
              $to .= $user->owner_guid;
        $to .= ",";  
      }
      }
    }
  }

    
  


  
    //Create Notifcation subject and body
   $ftObj = get_entity($object->owner_guid);
   $creator = $ftObj->name;
  
   $subject = elgg_echo('follow_tags:notification:subject');
  
   $body = elgg_echo('follow_tags:notification:body');
   $body .= "<br>" ;
   $body .= "$creator";
   $body .= elgg_echo('follow_tags:notification:body:creator');
   $body .= "<br>" ;
   $body .= $object->getURL();

    
    //Prefend empty array element

    $lastChar = substr($to, -1);
    if($lastChar == ','){
       $to = substr_replace($to,"",-1);
    }
    
    
    
    //Create acceptor-Array
    $toArray = explode(",",$to);

    // Notify user 
    // 1 is sender id from the elgg site

    notify_user($toArray, 1, $subject, $body, NULL);

    
}//Close subtype if 
}



function getAllTags(){


    $threshold = elgg_get_plugin_setting("threshold", "follow_tags");  
    if(!$threshold) {
        $threshold = 2; // Set Default threshold
    }

    if(elgg_get_plugin_setting("autocomplete", "follow_tags") == 'yes'){
  
    $option = array('limit'=>100000,
            'threshold' => $threshold,
            );
    $tags =elgg_get_tags($option);
  
  foreach ($tags as $tag) {
    $text .= "$tag->tag,";
  }
    $text .= elgg_get_plugin_setting("defaultTags", "follow_tags");
    // Append the default Tags 
    
    $php_array = explode(",",$text);
    $js_array = json_encode($php_array);
  
    }else{
  
    $js_array = json_encode("");
    }
    

return $js_array;

}






