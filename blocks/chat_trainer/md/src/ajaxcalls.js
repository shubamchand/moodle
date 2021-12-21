define('block/block_chat_trainer',['jquery', 'core/notification','core/ajax'],
       function($, notification, ajax) {
        return {
             init : function(a) {
               console.log('trainer chat is ready..')          
            },

            sendMessageSingle: function(obj,conversation_data){
                messageEle = $("#trainer-chat-btn-input");
                    ajax.call([{
                        methodname: 'core_message_send_instant_messages_trainer',
                        args: { messages: [{
                            text: messageEle.val(),
                            touserid: '124'
                        }]
                    },
                        done: this._handleNewConversationResponse.bind(this, conversation_data, 2),
                        fail: notification.exception
                    }]);

                },
            _handleNewConversationResponse: function(currentObj, res, response){
                return true; // no need to handle from code as its will handled call back in pusher
                // console.log(currentObj);
                // console.log(res);
                // console.log(response);
                // var message = response[0];
                // var liEle = this._handleSingleMessage(response[0],response,{userid: message.useridfrom});
                // messageList = `<ul class="chat-list-ul collapse show" id="${message.conversationid}">${liEle}</ul>`;
                // $(".accordion-group").append(messageList);
                // conversationListItem =`<button data-userid="${message.useridfrom}"  class="trainer-chat-group left clearfix message send clickable d-flex flex-column p-2 mx-1 position-relative rounded mb-2 mt-2" data-chat_heading="${message.useridfrom}" data-target="#${message.conversationid}" data-parent="#message-list" data-conversationid="${message.conversationid}"  data-toggle="collapse">${message.text}</button>`;
                // $("#message-list").append(conversationListItem);
                // $(conversationListItem).click();
            },


            _handleNewConversationResponseNotification: function(currentObj, response){
                $("body").find('.chat-list-ul').removeClass('show');
                var message = response.messages[0];
                var typingEle = $(".typing-indicator");
                if(typingEle.data('userid')==response.userid){
                    typingEle.attr('id','typing_indicator_'+message.conversationid);
                }
               
                var liEle = this._handleSingleMessage(response.messages[0],response,{userid: response.userid});
                messageList = `<ul class="chat-list-ul collapse show" id="${message.conversationid}">${liEle}</ul>`;
                var message_time = this.timeSince(message.timecreated)
                $(".accordion-group").append(messageList);
                conversationListItem =`<li data-userid="${message.useridfrom}"  class="trainer-chat-group left clearfix message send clickable d-flex flex-column p-2 mx-1 position-relative rounded mb-2 mt-2" data-chat_heading="${response.user_from}" data-target="#${message.conversationid}" data-parent="#message-list" data-conversationid="${message.conversationid}"  data-toggle="collapse">
                <small><i>${response.user_from}</i><i class="pull-right">${message_time}</i></small><p> <span class="trainer_last_message color-red"> ${message.text}<small><i class="pull-right">${response.user_from}</i></small></p>
                </li>`;
                $("#message-list").prepend(conversationListItem);
                $(conversationListItem).click();
            },

            sendMessageSingleConversation: function(data){
                messageEle = $("#trainer-chat-btn-input");
                ajax.call([{
                    methodname: 'core_message_send_messages_to_conversation',
                    args: { conversationid: data.conversationid,messages: [{
                        text: messageEle.val(),
                    }]
                },
                    done: this._handleMessageConversationResponse.bind(this, data, 1),
                    fail: notification.exception
                }]);

            },

            _handleMessageConversationResponse: function(currentObj, res, response){
                return true; // no need to handle message update as pusher notification will able to to handle it
                // var conversationUl = $("#"+currentObj.conversationid);
                // conversationUl.addClass('show');
                // var liEle = this._handleSingleMessage(response[0],response,currentObj);
                // conversationUl.append(liEle);

            },

            _handleMessageConversationNotification: function(currentObj,data){
                var conversationUl = $("#"+currentObj.conversationid);
                conversationUl.addClass('show');
                var liEle = this._handleSingleMessage(data.messages[0],data,currentObj);
                conversationUl.append(liEle);
            },

            addTrainerToConversation: function(conversationid,userid, type){ // add user to conversation 
                ajax.call([{
                    methodname: 'core_message_add_trainer_to_conversation',
                    args: { conversationid: conversationid, userid: userid, type: type},
                    done: this._handleAddToTrainer.bind(this, conversationid, 1),
                    fail: notification.exception
                }]);
            },

            _handleAddToTrainer: function(currentObj,res,response){
                return true;

            },

            sendMessageToConversation: function(){

            },
            

            loadConversations: function(userid,trainer_id = 124){
                ajax.call([{
                    methodname: 'core_message_get_conversations',
                    args: { messages: [{
                        text: messageEle.val(),
                        touserid: trainer_id
                    }]
                },
                    done: this._handleFormSubmissionResponse.bind(this, obj, 2),
                    fail: notification.exception
                }]);
            },
            loadConversation: function(userid){

            },

            loadConversationMesasges(data,userid){
                ajax.call([{
                    methodname: 'core_message_get_conversation_messages',
                    args: { 
                        convid: data.conversationid,
                        limitfrom: 0,
                        limitnum: 101,
                        newest: false,
                        currentuserid: userid,
                    },
                
                    done: this._handleFormSubmissionResponseList.bind(this, data, 1),
                    fail: notification.exception
                }]);
            },

            _handleFormSubmissionResponseList(currentObj, res, response) {
                // console.log(currentObj);
                var conversationUl = $("#"+currentObj.conversationid);
                conversationUl.addClass('show');
                conversationUl.empty();
                var liEle = '';
                for(let item of response.messages){
                    liEle += this._handleSingleMessage(item,response,currentObj);
                }
                conversationUl.html(liEle);

            },

            _handleSingleMessage(message,response = null,currentObj){
                var message_time = this.timeSince(message.timecreated)
                var user_name = this.getChatUserName(response.members,message,currentObj);
                var output = '';
                output += `<li class="left clearfix message send clickable d-flex flex-column p-2 mx-1 position-relative rounded mb-2 mt-2"><div class="chat-body clearfix">`;
                output += `<div class="header">`;
                output += `<strong class="primary-font">${user_name}</strong> <small class="pull-right text-muted">`;
                output += `<span class="glyphicon glyphicon-time"></span>${message_time}</small>`;
                output += `</div>`
                output += `${message.text}`;
                output += ` </div> </li>`;
                return output;
            },

              markConversationRead: function(conversationid,userid){
                ajax.call([{
                    methodname: 'core_message_mark_all_conversation_messages_as_read',
                    args: { conversationid: conversationid,userid : userid },
                    done: this._handleConversationRead.bind(this, conversationid, 1),
                    fail: notification.exception
                }]);

            },

            _handleConversationRead(){

            },

            getChatUserName(members,message,currentObj){
                // find the first occurrence of item with name "k1"
                
                if(message.useridfrom == currentObj.userid || message.useridfrom == currentObj.userid){
                    return '';
                } else if(currentObj.full_name){
                    return currentObj.full_name
                }else {
                    let user = members.find(item=>item.id==message.useridfrom);
                    if(user){
                        return user.fullname;
                    } else {
                        return '';
                    }
                }
                
            },

            timeSince(timeStamp) {
                var date = new Date(timeStamp * 1000);
                var now = new Date(),
                  secondsPast = (now.getTime() - date.getTime()) / 1000;
                if (secondsPast < 60) {
                   var second = parseInt(secondsPast);
                    if(second<0){
                        return '0s';
                    } else {
                        return second + 's';
                    }
                }
                if (secondsPast < 3600) {
                  return parseInt(secondsPast / 60) + 'm';
                }
                if (secondsPast <= 86400) {
                  return parseInt(secondsPast / 3600) + 'h';
                }
                if (secondsPast > 86400) {
                 
                  day = date.getDate();
                  month = date.toDateString().match(/ [a-zA-Z]*/)[0].replace(" ", "");
                  year = date.getFullYear() == now.getFullYear() ? "" : " " + date.getFullYear();
                  return day + " " + month + year;
                }
              }


        }
});