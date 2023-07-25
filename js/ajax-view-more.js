$(document).ready(function(){
    // Load more data
    $('button.js-gpu-log-view-more').click(function(){
        var row = Number($('#row').val());
        var allcount = Number($('#all').val());
        var rowperpage = 10;
        row = row + rowperpage;

        if(row <= allcount){
            $("#row").val(row);

            $.ajax({
                url: 'ajax_grab_log.php?nocache='+Math.random(), // Prevent cache
                method: 'post',
                data: {row:row},
                cache: 'false',
                beforeSend:function(){
                    $("button.js-gpu-log-view-more > span").text("Loading..");
                    //$(".loader").show().fadeIn("slow");
                    $("button.js-gpu-log-view-more > img").toggle();
                },
                success: function(response){
                    // Check if there is an element with class "post"
                    if($(".post").length > 0){
                        $(".post:last").after(response).show().fadeIn("slow");
                    }else{
                        // Handle case when no element with class "post" exists
                        $("#container").append(response).show().fadeIn("slow");
                    }

                    var rowno = row + rowperpage;

                    // checking row value is greater than allcount or not
                    if(rowno > allcount){
                        // Change the text and background
                        $("button.js-gpu-log-view-more > span").text("Hide");
                        $("button.js-gpu-log-view-more > img").toggle();
                    }else{
                        $("button.js-gpu-log-view-more > span").text("View more..");
                    }

                }
            });
        }  else {
            $("button.js-gpu-log-view-more > span").text("Loading..");
            $("button.js-gpu-log-view-more > img").toggle();

            // When row is greater than allcount then remove all class='post' element after 10 element
            $('.post:nth-child(10)').nextAll('.post').remove();

            // Reset the value of row
            $("#row").val(0);

            // Change the text and background
            $("button.js-gpu-log-view-more > span").text("View more..");
            $("button.js-gpu-log-view-more > img").toggle();
        }
    });
});