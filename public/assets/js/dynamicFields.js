        function insertHTML() {
            const newRow = document.createElement('tr');
            newRow.innerHTML = '<td><input class="form-control-sm" size="5" style="background-color: white;color: black !important;" type="text" name="phase[]" value="98-09000" placeholder="Phase" readonly></td>' 
			+ 
			'<td><input class="form-control-sm" type="text" size="25" style="background-color: white;color: black !important;" name="description[]" value="Unestimated Production - '+String(table.rows.length)+'" placeholder="Description" readonly></td>' 
			+ 
			'<td><input class="form-control-sm" style="background-color: white;color: black !important;" type="number" name="qty[]" value="0" placeholder="0"></td>' 
			+ 
			'<td><select style="background-color: white;color: black !important;" class="form-control-sm" name="unit_of_measure[]" required><option value="">Make a selection</option><option value"LM">Linear Miles</option><option value"LF">Linear Feet</option><option value"GM">Gross Miles</option><option value"GF">Gross Feet</option><<option value"GLF">Gross Linear Feet</option><option value"SF">Square Feet</option><option value"SY">Square Yards</option></select></td>' 
			+ 
			'<td><input class="form-control-sm" style="background-color: white;color: black !important;" type="text" name="mark_mill[]" placeholder="Mark Mill" value="NA"></td>' 
			+ 
			'<td><input class="form-control-sm" style="background-color: white;color: black !important;" type="text" name="road_name[]" placeholder="Road"></td>' 
			+ 
			'<td><input class="form-control-sm" style="background-color: white;color: black !important;" type="checkbox" name="phase_item_complete[]" placeholder="Phase Complete"></td>' 
			+ 
			'<td><select class="form-control-sm" style="background-color: white;color: black !important;" name="surface_type[]"><option value="NA">NA</><option value"Milling">Milling</><option value"Traffic Shift">Traffic Shift</><option value"Patch/Repair">Patch/Repair</><option value"Leveling">Leveling</><option value"Detour">Detour</><option value"Final">Final</><option value="Current">Current</></select></td>' 
			+ 
			'<td><input class="form-control-sm" style="background-color: white;color: black !important;" type="checkbox" name="po[]" placeholder="Purchase Order"></td>' 
			+ 
			'<td><input type="button" class="btn-warning" value="Delete Row" onclick="SomeDeleteRowFunction(this)"></td>';
            // Append the new row to the table
            table.appendChild(newRow);
        }

        function insertHTML2() {
            const newRow2 = document.createElement('tr');
            newRow2.innerHTML = '<td><input class="form-control-sm" size="15" style="background-color: white;color: black !important;" type="text" name="mphase[]" value="98-19999" placeholder="Phase" readonly></td>' 
			+ 
			'<td><input class="form-control-sm" type="text" size="25" style="background-color: white;color: black !important;" name="mdescription[]" value="Unestimated Materials - '+String(table2.rows.length)+'" placeholder="Description" readonly></td>' 
			+ 
			'<td><input class="form-control-sm" style="background-color: white;color: black !important;"  type="number" name="mqty[]" value="0" placeholder="0"></td>' 
			+ 
			'<td><select style="background-color: white;color: black !important;" class="form-control-sm" name="munit[]"><option value="">Make a selection</option><option value"GAL">Gallons</option><option value"EA">Each</option><option value"TON">Tons</option><<option value"FT">Feet</option><option value"SY">Square Yards</option></select></td>' 
			+ 
			'<td><input class="form-control-sm" style="background-color: white;color: black !important;" type="text" name="msupplier[]" placeholder="Supplier"></td>' 
			+ 
			'<td><input class="form-control-sm" style="background-color: white;color: black !important;" type="text" name="mbatch[]" placeholder="Batch"></td>' 
			+ 
			'<td><input type="button" class="btn-warning" value="Delete Row" onclick="SomeDeleteRowFunction(this)"></td>';
            // Append the new row to the table
            table2.appendChild(newRow2);
        }

        function insertHTML3() {
            const newRow3 = document.createElement('tr');
            newRow3.innerHTML = '<td><input class="form-control-sm" size="5" style="background-color: white;color: black !important;" type="text" name="ephase[]" @if (isset($equipment[0]->phase)) value="{{ $equipment[0]->phase }}" @else value="10-10000" @endif placeholder="Phase" disabled></td>' + '<td><input class="form-control-sm" type="text" size="25" style="background-color: white;color: black !important;" name="edescription[]" @if (isset($equipment[0]->description)) value="{{ $equipment[0]->description }}" @else value="Added Equipment - '+String(table3.rows.length)+'" @endif  placeholder="Added Equipment" disabled></td>' + '<td><select style="background-color: white;color: black !important;" class="form-control-sm" name="etruck[]"><option value="">Make a selection</option><option value="3-99">Crew Cab Truck</option><option value="10-99">Paint Truck</option><option value="21-99">Haul Truck</option><option value="30-99">Longline Truck</option><option value="32-99">Handline Truck</option><option value="37-99">Marker Truck</option><option value="38-99">Sealer Truck</option><option value="39-99">Knock Up Truck</option><option value="40-99">Removal Truck</option><option value="42-96">Vacuum Truck</option><option value="42-97">Tape Truck</option><option value="42-98">Waterblast Truck</option><option value="42-99">Epoxy Truck</option></select></td>' + '<td><input class="form-control-sm" style="background-color: white;color: black !important;" type="number" step="0.01" name="ehours[]" value="0" placeholder="0"></td>' + '<td><input type="button" class="btn-warning" value="Delete Row" onclick="SomeDeleteRowFunction(this)"></td>';
            // Append the new row to the table
            table3.appendChild(newRow3);
        }

        function addItem(id, desc, est, unit) {
            if (parseInt(id.substring(3, 8)) < 10000) {
                const production = document.createElement('tr');
                production.innerHTML = 
				'<td><input class="form-control-sm" size="5" style="background-color: white;color: black !important;" type="text" name="phase[]" value="' + id + '" placeholder="Phase" readonly></td>' 
				+ 
				'<td><input class="form-control-sm" type="text" size="25" style="background-color: gray;width: 100%;vertical-align: top;border-color: #f0f2f5;" name="description[]" value="' + desc + '" placeholder="Description" readonly></td>' 
				+ 
				'<td><input class="form-control-sm" style="background-color: white;color: black !important;" type="number" name="qty[]" placeholder="0" value="0" ></td>' 
				+ 
				'<td><input class="form-control-sm" style="background-color: gray;width: 100%;vertical-align: top;border-color: #f0f2f5;" type="text" name="unit_of_measure[]"  value="' + unit + '" placeholder="Unit of Measure" readonly></td>' 
				+ 
				'<td><input class="form-control-sm" style="background-color: white;color: black !important;" type="text" name="mark_mill[]" placeholder="Mark Mill"></td>' + '<td><input class="form-control-sm" style="background-color: white;color: black !important;" type="text" name="road_name[]" placeholder="Road"></td>' 
				+ 
				'<td><input class="form-control-sm" type="checkbox" name="phase_item_complete[]" placeholder="Phase Complete"></td>' 
				+ 
				'<td><select class="form-control-sm" style="background-color: white;color: black !important;" name="surface_type[]"><option value="NA">NA</><option value"Milling">Milling</><option value"Traffic Shift">Traffic Shift</><option value"Patch/Repair">Patch/Repair</><option value"Leveling">Leveling</><option value"Detour">Detour</><option value"Final">Final</><option value="Current">Current</></select></td>' + '<td><input class="form-control-sm" style="background-color: white;width: 100%;vertical-align: top;border-color: #f0f2f5;" type="checkbox" name="po[]" placeholder="Purchase Order"></td>' 
				+ 
				'<td><input type="button" class="btn-warning" value="Delete Row" onclick="SomeDeleteRowFunction(this)"></td>';
                // Append the new row to the table
                table.appendChild(production);
            }
            if (parseInt(id.substring(3, 8)) == 10000) {
                const material = document.createElement('tr');
                material.innerHTML = 
				'<td><input class="form-control-sm" size="5" style="color: black;border-spacing: 2px;display: table-cell; vertical-align: inherit;background-color: gray;width: 100%;vertical-align: top;border-color: #f0f2f5;" type="text" name="phase[]" value="' + id + '" placeholder="Phase" readonly></td>' 
				+ 
				'<td><input class="form-control-sm" type="text" size="25" style="background-color: gray;width: 100%;vertical-align: top;border-color: #f0f2f5;" name="description[]" value="' + desc + '" placeholder="Description" readonly></td>' 
				+ 
				'<td><select style="background-color: white;color: black !important;" class="form-control-sm" name="etruck[]"><option value="">Make a selection</option><option value="3-99">Crew Cab Truck</option><option value="10-99">Paint Truck</option><option value="21-99">Haul Truck</option><option value="30-99">Longline Truck</option><option value="32-99">Handline Truck</option><option value="37-99">Marker Truck</option><option value="38-99">Sealer Truck</option><option value="39-99">Knock Up Truck</option><option value="40-99">Removal Truck</option><option value="42-96">Vacuum Truck</option><option value="42-97">Tape Truck</option><option value="42-98">Waterblast Truck</option><option value="42-99">Epoxy Truck</option></select></td>'
				+ 
				'<td><input class="form-control-sm" style="background-color: white;color: black !important;" type="number" name="ehours[]" placeholder="0" value="0"></td>' 
				+ 
				'<td><input type="button" class="btn-warning" value="Delete Row" onclick="SomeDeleteRowFunction(this)"></td>';
                // Append the new row to the table
                table3.appendChild(material);
            }
            if (parseInt(id.substring(3, 8)) > '10000') {
                const equipment = document.createElement('tr');
                equipment.innerHTML = 
				'<td><input class="form-control-sm" size="5" style="color: black;border-spacing: 2px;display: table-cell; vertical-align: inherit;background-color: gray;width: 100%;vertical-align: top;border-color: #f0f2f5;" type="text" name="mphase[]" value="'+ id + '" placeholder="Phase" readonly></td>'
				+ 
				'<td><input class="form-control-sm" type="text" size="25" style="background-color: gray;width: 100%;vertical-align: top;border-color: #f0f2f5;" name="mdescription[]" value="' + desc + '" placeholder="Description" readonly></td>' 
				+ 
				'<td><input class="form-control-sm" style="background-color: white;color: black !important;" type="number"  name="mqty[]" placeholder="0" value="0" ></td>' 
				+ 
				'<td><input class="form-control-sm" style="background-color: gray;width: 100%;vertical-align: top;border-color: #f0f2f5;" type="text" name="munit[]"  value="' + unit + '" placeholder="Unit of Measure" readonly></td>' 
				+ 
				'<td><input class="form-control-sm" style="background-color: white;color: black !important;" type="text" name="msupplier[]" placeholder="Supplier"></td>' 
				+ 
				'<td><input class="form-control-sm" style="background-color: white;color: black !important;" type="text" name="mbatch[]" placeholder="Batch #"></td>' 
				+ 
				'<td><input type="button" class="btn-warning" value="Delete Row" onclick="SomeDeleteRowFunction(this)"></td>';
                // Append the new row to the table
                table2.appendChild(equipment);
            }
        }