/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

$(document).ready(function() {
	
	$('#searchform').on('submit', function(event) {
		
		event.preventDefault();
		
		$("*").css("cursor", "progress");
		
		$('#querieswrapper').fadeIn();
		
		var serializedData = $(this).serialize();
		
		$.ajax({
			url: "/search.php",
			type: "post",
			data: serializedData,
			dataType: "json",
			success: function(data){
				
				var liString = "<li><a class='searchlink' SearchId='"+data.SearchId+"' href='javascript://'>"+data.q+"</a></li>";
				
				$('#queries ul').prepend(
					$(liString).hide().fadeIn('slow')
				);
			},
			error:function(){
				alert("search failed");
			}
		});
		
		$("*").css("cursor", "default");
		
	});
	
	$('#queries').on('click', 'a', function() {
		var SearchId = $(this).attr('SearchId');
		var SearchName = $(this).text();
		
		$("*").css("cursor", "progress");
		
		$('#results').fadeOut();
		
		$('#tweets ul').empty();
		
		$.ajax({
			url: "/searchresults.php",
			type: "post",
			data: 'SearchId='+SearchId,
			dataType: "json",
			success: function(jsondata){
				
				
				$('.SearchName').html(SearchName);
				
				for (var i=0; i<jsondata.Data.length; i++) {
					var liString = "<li><a target='_blank' href='https://twitter.com/"+jsondata.Data[i].UserId+"/status/"+jsondata.Data[i].TwitterId+"' class='tweetlink'>"+jsondata.Data[i].Tweet+"</a></li>";

					$('#tweets ul').prepend(
						$(liString).hide().fadeIn('slow')
					);
						
				}
				
				var thisdata = jsondata.ChartData;
				data = new Array();
				var colorcounter = 0;
				for (var j=0; j<categories.length; j++ ) {
					if (colorcounter == 10) {
						colorcounter = 0;
					}
					//console.log(thisdata[categories[j]]);
					var thisitem = thisdata[categories[j]];
					data[j] = {
									y: thisitem.y,
									color: colors[colorcounter],
									drilldown: {
										name: thisitem.drilldown.name,
										categories: thisitem.drilldown.categories,
										data: thisitem.drilldown.data,
										color: colors[colorcounter]
									}
								};
					colorcounter++;
				}
				
				
				
				$('#results').fadeIn();
		
				DrawChart();
				
			},
			error:function(){
				alert("search failed");
			}
		});
		$("*").css("cursor", "default");
	});
   
   
	var colors = Highcharts.getOptions().colors;
	var categories = ['a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z'];
	var name = 'Tweets by First Letter';
	var chart, data;

	function setChart(name, categories, data, color) {
		chart.xAxis[0].setCategories(categories, false);
		chart.series[0].remove(false);
		chart.addSeries({
			name: name,
			data: data,
			color: color || 'white'
		}, false);
		chart.redraw();
	}
	

	function DrawChart() {
		if (typeof variable !== 'undefined') {
			chart.destroy();
		}
		
        chart = $('#graph').highcharts({
            chart: {
                type: 'column'
            },
            title: {
                text: 'First Letter of Tweets'
            },
            subtitle: {
                text: 'Click the columns to view time intervals'
            },
            xAxis: {
                categories: categories
            },
            yAxis: {
                title: {
                    text: 'Number of Tweets'
                }
            },
			loading: {
				hideDuration: 1000,
				showDuration: 1000
			},
            plotOptions: {
                column: {
                    cursor: 'pointer',
                    point: {
                        events: {
                            click: function() {
                                var drilldown = this.drilldown;
                                if (drilldown) { // drill down
                                    setChart(drilldown.name, drilldown.categories, drilldown.data, drilldown.color);
                                } else { // restore
                                    setChart(name, categories, data);
                                }
                            }
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        color: colors[0],
                        style: {
                            fontWeight: 'bold'
                        },
                        formatter: function() {
                            return this.y;
                        }
                    }
                }
            },
            tooltip: {
                formatter: function() {
                    var point = this.point,
                        s = this.x +':<b>'+ this.y +' tweets</b><br/>';
                    if (point.drilldown) {
                        s += 'Click to view tweets by time distribution';
                    } else {
                        s += 'Click to return to tweets by first letter';
                    }
                    return s;
                }
            },
            series: [{
                name: name,
                data: data,
                color: 'white'
            }],
            exporting: {
                enabled: false
            }
        })
        .highcharts(); // return chart
	}
});
