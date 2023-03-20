<div class="col-md-4">
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title"><i class="fa fa-frown-o"></i>
                <span data-i18n="firmware.ibridge_outdated"></span>
                <list-link data-url="/show/listing/firmware/firmware"></list-link>
            </h3>
        </div>
        <div id="firmware-ibridge_outdated-panel" class="panel-body text-center">
            <svg id="firmware-ibridge_outdated-plot" style="width:100%; height: 300px"></svg>
        </div>
    </div><!-- /panel -->
</div><!-- /col -->

<script>
    $(document).on('appReady', function() {

        function isnotzero(point)
        {
            return point.count > 0;
        }

        var url = appUrl + '/module/firmware/get_ibridge_outdated'
        var chart;
        d3.json(url, function(err, data){

            var height = 300;
            var width = 350;

            // Filter data
            data = data.filter(isnotzero);

            nv.addGraph(function() {
                var chart = nv.models.pieChart()
                    .x(function(d) { return d.lable })
                    .y(function(d) { return d.count })
                    .showLabels(false);

                chart.title("" + d3.sum(data, function(d){
                    return d.count;
                }));

                chart.pie.donut(true);

                d3.select("#firmware-ibridge_outdated-plot")
                    .datum(data)
                    .transition().duration(1200)
                    .style('height', height)
                    .call(chart);

                // Adjust title (count) depending on active slices
                chart.dispatch.on('stateChange.legend', function (newState) {
                    var disabled = newState.disabled;
                    chart.title("" + d3.sum(data, function(d, i){
                        return d.count * !disabled[i];
                    }));
                });

                return chart;
            });
        });
    });
</script>