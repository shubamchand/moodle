/*global $:false */
/*exported togglecollapseall */
/* The above disables warnings for undefined '$' and unused 'togglecollapseall'. */
/*
 * Collapse/Expand all courses/assessments. If we are in the course,
 * then only collapse/expand all assessments.
 */
// added by nirmal to fix the js toggling issue specific to the module
$(window).on('load',function(){
   
    // Enable pusher logging - don't include this in production
        var amdModule = require('block/block_chat_trainer');
        console.log(amdModule);
	    Pusher.logToConsole = true;
        var is_typing = false;
        var pusher = new Pusher('03537f8d0e9c7622f362', {
            cluster: 'mt1'
        });
	
		var channel = pusher.subscribe('brief-star-163');
		channel.bind('my-event', function(data) {
		  console.log(JSON.stringify(data));
		});

		channel.bind('event_typing', function(data) {
            var typingEle = $("#typing_indicator_"+data.conversationid);
            if(typingEle.data('userid')!=data.userid){
                typingEle.html(data.user_event)
            }
            
		});

		channel.bind('new_message_conversation', function(data) {
            // console.log(data)
            // var amdModule = require('block/block_chat_trainer');
            var typingEle = $(".typing-indicator");
            
            amdModule._handleMessageConversationNotification({conversationid:data.conversationid, full_name: data.user_from, userid: typingEle.data('userid')  },data);
		});

		channel.bind('new_message_trainer', function(data) {
            // var amdModule = require('block/block_chat_trainer');
            var typingEle = $(".typing-indicator");
            console.log(data);
            var userid = typingEle.data('userid');
            conversation_data.conversationid = data.conversationid;
            conversation_data.userid = userid;
            amdModule._handleNewConversationResponseNotification({conversationid:data.conversationid, full_name: data.user_from, userid: userid  },data);
		});


        
    var conversation_data = {};
    function getConversations(){
        // var amdModule = require('block/block_chat_trainer');
        amdModule.loadConversations({});
    }
    $("body").on('click','.trainer-chat-group',function(){
       var obj = $(this);
       console.log(obj.data());
       var typingEle = $(".typing-indicator");
       typingEle.attr('id','typing_indicator_'+obj.data('conversationid'));
       $("#new-conversation").removeClass('show');
       conversation_data = obj.data();
       $("#chat-heading").html(obj.data('chat_heading'))
       var parent = obj.parent()
       parent.removeClass('show');
       $(".back-to-list").addClass('show');
       $("#chat-trainer-chatbox").addClass('show');
       
   })

   $("#new-conversation").on('click',function(){
       var obj = $(this);
       conversation_data = obj.data();
       conversation_data.conversationid = 'new';
       console.log(obj.data())
       console.log(conversation_data);
       $("#chat-heading").html(obj.data('chat_heading'))
       $("#message-list").removeClass('show');
       $("#new-conversation").removeClass('show');
       $("#chat-trainer-chatbox").addClass('show');
       setTimeout(() => {$(".back-to-list").addClass('show')}, 500);
     
       
       
   })

   $(".back-to-list").on('click',function(){
       var obj = $(this);
       obj.removeClass('show');
       var parent = obj.parent()
       parent.removeClass('show');
       $("#chat-trainer-chatbox").removeClass('show');
       $(".chat-list-ul").removeClass('show');
       $("#chat-heading").html('')
       $(".back-to-list-new").addClass('show');
       $("#new-conversation").addClass('show');
   })

   $("#trainer-btn-chat").on('click',function(){
        var obj = $(this);
        sendMessage(obj);
        
   })

   function sendMessage(obj){
    var chatInput = $("#trainer-chat-btn-input");
    if(chatInput.val().length>0){
        if(conversation_data.conversationid=='new'){
            amdModule.sendMessageSingle(obj,conversation_data);
        } else {
            amdModule.sendMessageSingleConversation(conversation_data);
        }
        chatInput.val('');
    } else {

    }
   }

//    sendChatMessage

   $("#trainer-chat-btn-input").keydown(function(ev){
        var keycode = (ev.keyCode ? ev.keyCode : ev.which);
        if (keycode == '13') {
            ev.preventDefault();
            var obj = $(this);
            sendMessage(obj);
        } else{
            if(is_typing==false && conversation_data.conversationid!='new'){
                is_typing = true;
                // var amdModule = require('block/block_chat_trainer');
                var response = amdModule.addTrainerToConversation(conversation_data.conversationid,conversation_data.userid,1);
                resetTyping();
            }
        }
       
   })

   function resetTyping(){
    setTimeout(function(){ true; }, 1000);
   }

   $("#trainer-chat-btn-input").focusout(function(){
       if(is_typing==true && conversation_data.conversationid!='new'){
        //    var amdModule = require('block/block_chat_trainer');
           var response = amdModule.addTrainerToConversation(conversation_data.conversationid,conversation_data.userid,2);
           is_typing = false;
       }
   })
   
 



   $("body").on('click','.trainer-chat-group',function(){
        var obj = $(this);
        conversation_data = obj.data();
        var typingEle = $(".typing-indicator");
        if(conversation_data.conversationid!=='new'){
            // var amdModule = require('block/block_chat_trainer');
            // if(conversation_data.is_in_conversation=='no'){
                // var response = amdModule.addTrainerToConversation(conversation_data.conversationid,conversation_data.userid);
            //     console.log(response);
            // }
            // check if trainer is in conversation if not than need to add trainer in conversation than only load message converation
            amdModule.loadConversationMesasges(obj.data(),typingEle.data('userid'));
            amdModule.markConversationRead(obj.data('conversationid'),typingEle.data('userid'))
        }
   })
})