<?php
$days = (isset($_GET['days']))?$_GET['days']:7;
$start = (isset($_GET['start']))?$_GET['start']:0;
?>

<!DOCTYPE html>
<meta charset="utf-8">
<style>

.bar--positive {
  fill: green;
}

.bar--negative {
  fill: red;
}

.axis text {
  font: 10px sans-serif;
}

.axis path,
.axis line {
  fill: none;
  stroke: #000;
  shape-rendering: crispEdges;
}

</style>
<body>
<script src="//d3js.org/d3.v3.min.js"></script>
<script>

var margin = {top: 20, right: 30, bottom: 40, left: 30},
    width = 960 - margin.left - margin.right,
    height = 500 - margin.top - margin.bottom;

var x = d3.scale.linear()
    .range([0, width]);

var y = d3.scale.ordinal()
    .rangeRoundBands([0, height], 0.1);

var xAxis = d3.svg.axis()
    .scale(x)
    .orient("bottom");

var yAxis = d3.svg.axis()
    .scale(y)
    .orient("left")
    .tickSize(0)
    .tickPadding(6);

var svg = d3.select("body").append("svg")
    .attr("width", width + margin.left + margin.right)
    .attr("height", height + margin.top + margin.bottom)
  .append("g")
    .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

d3.tsv("gains_data.php?days=<?php echo $days;?>&start=<?php echo $start;?>", type, function(error, data) {
  x.domain(d3.extent(data, function(d) { return d.gains; })).nice();
  y.domain(data.map(function(d) { return d.jour; }));

  svg.selectAll(".bar")
      .data(data)
    .enter().append("rect")
      .attr("class", function(d) { return "bar bar--" + (d.gains < 0 ? "negative" : "positive"); })
      .attr("x", function(d) { return x(Math.min(0, d.gains)); })
      .attr("y", function(d) { return y(d.jour); })
      .attr("width", function(d) { return Math.abs(x(d.gains) - x(0)); })
      .attr("height", y.rangeBand());

  svg.append("g")
      .attr("class", "x axis")
      .attr("transform", "translate(0," + height + ")")
      .call(xAxis);

  svg.append("g")
      .attr("class", "y axis")
      .attr("transform", "translate(" + x(0) + ",0)")
      .call(yAxis);
});

function type(d) {
  d.gains = +d.gains;
  return d;
}

</script>

