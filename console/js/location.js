

function getLocation(){

    if(navigator.geolocation){
        navigator.geolocation.getCurrentPosition(function(position){
        	var coordinates = position.coords.latitude + ', ' + position.coords.longitude;

            $('#hdn_coords').val(position.coords.latitude + ', ' + position.coords.longitude);
        	//console.log('Position: ' + position.coords.latitude + ', ' + position.coords.longitude);
		});
    }else{
        var coordinates = null;
        console.log("Geolocation is not supported by this browser.");
    }

}

