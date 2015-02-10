OW_Notification = function( itemKey )
{
    var listLoaded = false;
    var model, list;

    //code

    model = OW.Console.getData(itemKey);
    list = OW.Console.getItem(itemKey);

    model.addObserver(function()
    {
        if ( !list.opened )
        {
            list.setCounter(model.get('counter.new'), true);
        }
    });

    list.onHide = function()
    {
        list.setCounter(0);
        list.getItems().removeClass('ow_console_new_message');
    };

    list.onShow = function()
    {
        if ( model.get('counter.all') <= 0 )
        {
            this.showNoContent();

            return;
        }

        if ( model.get('counter.new') > 0 || !listLoaded )
        {
            this.loadList();
            listLoaded = true;
        }
    };
}

OW.Notification = null;