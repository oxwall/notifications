var OWM_NotificationsConsole = function( params )
{
    var self = this;
    self.params = params;

    this.consoleLoadMore = function( $node )
    {
        $node.addClass("owm_sidebar_load_more_preloader");

        var exclude =
            $("li.owm_sidebar_msg_item", "#notifications-list")
                .map(function(){
                    return $(this).data("nid");
                })
                .get();

        OWM.loadComponent(
            "NOTIFICATIONS_MCMP_ConsoleItems",
            { limit:self.params.limit, exclude: exclude },
            {
                onReady: function(html){
                    $("#notifications-list").append(html);
                    $node.removeClass("owm_sidebar_load_more_preloader");
                }
            }
        );
    };

    this.hideLoadMoreButton = function()
    {
        $("#notifications-load-more").closest(".owm_sidebar_msg_list").hide();
    };

    $("body")
        .on("click", "a#notifications-load-more", function(){
            self.consoleLoadMore($(this));
        })
        .on("click", "#notifications-list li.owm_sidebar_msg_item", function(){
            var url = $(this).data("url");
            if ( url != undefined && url.length )
            {
                document.location.href = url;
            }
        });

    OWM.bind("mobile.console_hide_notifications_load_more", function(){
        self.hideLoadMoreButton();
    });

    OWM.bind("mobile.console_load_new_items", function(data){
        if ( data.page == 'notifications' && data.section == 'notifications' )
        {
            $("#notifications-list").prepend(data.markup);
        }
    });

    OWM.bind("mobile.hide_sidebar", function(data){
        if ( data.type == "right" )
        {
            OWM.unbind("mobile.console_hide_notifications_load_more");
            OWM.unbind("mobile.console_load_new_items");
            $("body")
                .off("click", "a#notifications-load-more");
        }
    });
};