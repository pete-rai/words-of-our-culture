
'use strict';

// --- use to wait to a late loaded control to appear

function whenPresent (control, action)
{
    if ($(control).length === 0)
    {
        setTimeout (whenPresent.bind (this, control, action), 500);
    }
    else
    {
        action ($(control));
    }
}

/*
    whenPresent (".foobar", function (control)
    {
        control.css ("opacity", "0.7");
    });

*/
