/*
*/

(function ($) {
    var options = {
        tooltip: {
        }
    };
    
    function init(plot) {
        // position of crosshair in pixels
        var crosshair = { x: -1, y: -1, locked: false };

        plot.setCrosshair = function setCrosshair(pos) {
        };
        
        plot.clearCrosshair = plot.setCrosshair; // passes null for pos
        
        plot.lockCrosshair = function lockCrosshair(pos) {
        }

        plot.unlockCrosshair = function unlockCrosshair() {
        }

        function onMouseLeave(e) {
		// #####
		plot.triggerTooltipEvent("tooltip",e);
        }

        function onMouseMove(e) {
		// #####
		plot.triggerTooltipEvent("tooltip",e);
        }

	plot.triggerTooltipEvent = function triggerTooltipEvent(eventname,event) {
//		if (console && console.debug) console.debug ("Plot TooltipEvent "+eventname,event);

	        var offset = plot.offset(),
                canvasX = event.pageX - offset.left,
                canvasY = event.pageY - offset.top;
/*,
	        pos = plot.canvasToAxisCoords({ left: canvasX, top: canvasY });

	        pos.pageX = event.pageX;
        	pos.pageY = event.pageY;
 
 	        pos.clientX = event.clientX;
        	pos.clientY = event.clientY;

		var item = plot.findNearbyItem(canvasX, canvasY, seriesFilter);

	        if (item) {
        	        // fill in mouse pos for any listeners out there
                	item.pageX = parseInt(item.series.xaxis.p2c(item.datapoint[0]) + offset.left + plotOffset.left);
	                item.pageY = parseInt(item.series.yaxis.p2c(item.datapoint[1]) + offset.top + plotOffset.top);
        	}

		if (item) {
			if (console && console.debug) console.debug ("TooltipEvent: show");
		} else {
			if (console && console.debug) console.debug ("TooltipEvent: hide");
		}
*/
	}
        
        plot.hooks.bindEvents.push(function (plot, eventHolder) {
/*            if (!plot.getOptions().crosshair.mode)
                return;*/
            eventHolder.mouseleave(onMouseLeave);
            eventHolder.mousemove(onMouseMove);
        });

        plot.hooks.shutdown.push(function (plot, eventHolder) {
            eventHolder.unbind("mouseleave", onMouseLeave);
            eventHolder.unbind("mousemove", onMouseMove);
        });
    }
    
    $.plot.plugins.push({
        init: init,
        options: options,
        name: 'tooltip',
        version: '0.5'
    });
})(jQuery);
