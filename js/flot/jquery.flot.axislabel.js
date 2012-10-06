/*
Pretty handling of axislabel axes.
*/
(function ($) {
    var options = {};

    function init(plot) {
        plot.hooks.processDatapoints.push(function (plot, series, datapoints) {
            $.each(plot.getAxes(), function(axisName, axis) {
                var opts = axis.options;
                if (opts.axisLabel) {
                    axis.labelGenerator = function() {} ;
                   	axis.labelGenerator.labelCalcsize = function(axis,ctx) {
                        ctx.save();
                        f=axis.font;
                        ctx.font = f.style + " " + f.variant + " " + f.weight + " " + f.size + "px '" + f.family + "'";
                        m = ctx.measureText(opts.axisLabel);
                        h= m.height != null ? m.height : f.size;
                        axis.labelWidth += h;
                        ctx.restore();
                        if (axis.direction == 'y') {
                            axis.labelWidth += h;
                        } else {
                            axis.labelHeight += h;
                        }
                    };

                    axis.labelGenerator.drawAxislabel = function (axis,ctx,x,y) {
                       f=axis.font;
                       box=axis.box;
                       ctx.save();
                       ctx.font = f.style + " " + f.variant + " " + f.weight + " " + f.size + "px '" + f.family + "'";
                        m = ctx.measureText(axis.options.axisLabel);
                       h= m.height != null ? m.height : f.size;
                       w= m.width;
                       if (axis.direction == 'y') {
                               if (axis.options.position=='left') {
                                       angle =  - Math.PI/2 ;
                                       ydiff = w/2;
                               } else {
                                       angle = Math.PI/2;
                                       ydiff = -w/2;
                                       x = x + box.width - box.padding;
                               }
                               y = (box.height / 2) + ydiff;
                               x = x-h;
                       } else {
                               angle=0
                       }
                        ctx.translate(x, y);
                        ctx.rotate(angle);
                        ctx.fillText(axis.options.axisLabel, 0, 0);
                       ctx.restore();

                    };
                };
            });
        });
    }

    $.plot.plugins.push({
        init: init,
        options: options,
        name: 'axislabel',
        version: '0.1'
    });
})(jQuery);
