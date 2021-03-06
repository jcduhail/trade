<?php 
$last = (isset($_GET['last']))?$_GET['last']:null;
$type = (isset($_GET['type']))?$_GET['type']:'data';
?>

<!DOCTYPE html>
<meta charset="utf-8">
<style>

.axis--x path {
  display: none;
}

.line {
  fill: none;
  stroke: steelblue;
  stroke-width: 1.5px;
}

</style>
<svg width="960" height="500"></svg>
<script src="//d3js.org/d3.v4.min.js"></script>
<script>

var svg = d3.select("svg"),
    margin = {top: 20, right: 80, bottom: 30, left: 50},
    width = svg.attr("width") - margin.left - margin.right,
    height = svg.attr("height") - margin.top - margin.bottom,
    g = svg.append("g").attr("transform", "translate(" + margin.left + "," + margin.top + ")");

var parseTime = d3.timeParse("%Y%m%d%H%M%S");

var x = d3.scaleTime().range([0, width]),
    y = d3.scaleLinear().range([height, 0]),
    z = d3.scaleOrdinal(d3.schemeCategory10);

var line = d3.line()
    .curve(d3.curveBasis)
    .x(function(d) { return x(d.time); })
    .y(function(d) { return y(d.rate); });

d3.tsv("data.php?last=<?php echo $last;?>&type=<?php echo $type;?>", type, function(error, data) {
  if (error) throw error;

  var rates = data.columns.slice(1).map(function(id) {
    return {
      id: id,
      values: data.map(function(d) {
        return {time: d.time, rate: d[id]};
      })
    };
  });

  x.domain(d3.extent(data, function(d) { return d.time; }));

  y.domain([
    d3.min(rates, function(c) { return d3.min(c.values, function(d) { return d.rate; }); }),
    d3.max(rates, function(c) { return d3.max(c.values, function(d) { return d.rate; }); })
  ]);

  z.domain(rates.map(function(c) { return c.id; }));

  g.append("g")
      .attr("class", "axis axis--x")
      .attr("transform", "translate(0," + height + ")")
      .call(d3.axisBottom(x));

  g.append("g")
      .attr("class", "axis axis--y")
      .call(d3.axisLeft(y))
    .append("text")
      .attr("transform", "rotate(-90)")
      .attr("y", 6)
      .attr("dy", "0.71em")
      .attr("fill", "#000")
      .text("Rate");

  var rate = g.selectAll(".rate")
    .data(rates)
    .enter().append("g")
      .attr("class", "rate");

  rate.append("path")
      .attr("class", "line")
      .attr("d", function(d) { return line(d.values); })
      .style("stroke", function(d) { return z(d.id); });

  rate.append("text")
      .datum(function(d) { return {id: d.id, value: d.values[d.values.length - 1]}; })
      .attr("transform", function(d) { return "translate(" + x(d.value.time) + "," + y(d.value.rate) + ")"; })
      .attr("x", 3)
      .attr("dy", "0.35em")
      .style("font", "10px sans-serif")
      .text(function(d) { return d.id; });
});

function type(d, _, columns) {
  d.time = parseTime(d.time);
  for (var i = 1, n = columns.length, c; i < n; ++i) d[c = columns[i]] = +d[c];
  return d;
}

</script>
