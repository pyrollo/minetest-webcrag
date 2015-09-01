--[[
Web Craft Guide minetest mod 
by P.Y. Rollo dev@pyrollo.com

Distributed under the terms of the GNU General Public License v3
]]--

local modpath = minetest.get_modpath("webcrag")

-- Read configuration file
dofile(modpath.."/config.lua")

datafile = "mtdata.json"

function convert_craft(craft)
	local ret = {}
 	local width = craft.width

	ret.type = craft.type
	if craft.type == "normal" and width == 0 then
		ret.type = "shapeless"
		width = 3
	end
	ret.output = craft.output

	ret.inputs = {}

	for y = 0,2 do
		for x = 0, width - 1 do
			ret.inputs[1 + y * 3 + x] = craft.items[1 + y * width + x]
		end
	end

	ret.cooktime = craft.cooktime
	ret.burntime = craft.burntime

	return ret
end

function webcrag_export() 
	local nitems = 0
	local ngroups = 0
	local ncrafts = 0
	local nusages = 0

	local export = {}
	local copiedimages = {}

	local export_start = os.clock()

	local function register_item(name) 
		if export.items[name] == nil then
			nitems = nitems + 1
			export.items[name] = {} 
			export.items[name].crafts = {}
			export.items[name].usages = {}
		end
	end

	local function register_group(name)
		if export.groups[name] == nil then
			ngroups = ngroups + 1
			export.groups[name] = {}
			export.groups[name].items = {}
			export.groups[name].usages = {}
		end
	end

	print ("[webcrag] Starting data eport")


	export.items = {}
	export.groups = {}

	-- Copy textures
	os.execute("mkdir -p "..webcrag_exportdir.."textures/")

	for _, name in ipairs(minetest.get_modnames()) do
		os.execute("cp -u "..minetest.get_modpath(name).."/textures/* "..webcrag_exportdir.."/textures/ > /dev/null 2>&1") 
	end

 	-- Export item data

	for name, data in pairs(minetest.registered_items) do
	    if not (data.type == "none" or name == "ignore" or name == "air") then

			register_item(name)
			export.items[name].name = data.name
			export.items[name].type = data.type
			export.items[name].description = data.description
			export.items[name].mod = name:match('(.*):')

			-- Groups
			export.items[name].groups = data.groups
			for	group,level in pairs(data.groups) do
				register_group(group)
				table.insert(export.groups[group].items, name)
			end 

			-- Display
			export.items[name].inventory_image = data.inventory_image
			export.items[name].tiles = data.tiles
			export.items[name].node_box = data.node_box

			-- Crafts
			local crafts = minetest.get_all_craft_recipes(name)
			if crafts then 
				for _,craft in pairs(crafts) do
					local converted_craft = convert_craft(craft)
					ncrafts = ncrafts + 1
					-- Add craft to item
					table.insert(export.items[name].crafts, converted_craft)

					-- Add usage to components items
					local components_items = {}
					for _,name in pairs(craft.items) do
						components_items [name] = name
					end
					for _,name in pairs(components_items) do
						if name:sub(1,6) == 'group:' then
							local group = name:sub(7)
							register_group(name:sub(7))
							table.insert(export.groups[group].usages, converted_craft)
						else
							register_item(name)
							table.insert(export.items[name].usages, converted_craft)
						end
						nusages = nusages + 1
					end
				end
			end
		end
	end -- All items loop

	-- Choose a group image (from item) for each group with usage
	for groupname, group in pairs(export.groups) do
		if #group.usages > 0 then
			-- Built-ins
			if groupname == 'wool' then
				group.image_item = group.items['wool:white']
			end

			-- Try to find an item with matching name
			if group.image_item  == nil then
				for _, name in pairs(group.items) do
					local itemname = name:match(':(.*)')
					if itemname == groupname then
						group.image_item = export.items[name]
						break
					end
				end
			end

			-- ... or take the first item found
			if group.image_item  == nil then
				for _, name in pairs(group.items) do
					group.image_item = export.items[name]
				end
	 		end
		end
	end

	local f = assert(io.open(webcrag_exportdir..datafile, "w"))
	local t = f:write(minetest.write_json(export))
	f:close()

	print (("[webcrag] Exported %i items, %i groups, %i crafts and %i usages."):format(nitems, ngroups, ncrafts, nusages))
	print (("[webcrag] Exported in file %s."):format(webcrag_exportdir..datafile))
	print (("[webcrag] Export done in %f seconds"):format(os.clock() - export_start))
end

-- Launch export 1s after server start
minetest.after(1, webcrag_export)

