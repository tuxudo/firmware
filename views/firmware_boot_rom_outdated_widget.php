<div class="col-md-4">
    <div class="card card-default">
        <div class="card-heading">
            <i class="fa fa-frown-o"></i>
            <span data-i18n="firmware.boot_rom_outdated"></span>
            <a href="/show/listing/firmware/firmware" class="pull-right"><i class="fa fa-list"></i></a>
        </div>
        <div id="firmware-boot_rom_outdated-card" class="card-body text-center">
            <svg id="firmware-boot_rom_outdated-plot" style="width:100%; height: 300px"></svg>
        </div>
    </div><!-- /card -->
</div><!-- /col -->

<script>
    $(document).on('appReady', function() {

        function isnotzero(point)
        {
            return point.count > 0;
        }

        var url = appUrl + '/module/firmware/get_boot_rom_outdated'
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

                d3.select("#firmware-boot_rom_outdated-plot")
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