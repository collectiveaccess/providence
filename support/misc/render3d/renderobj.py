# Execution :
# $ /Applications/blender/blender.app/Contents/MacOS/blender -b -P ~/test_blender.py -- Scene_2012_01_11_15_10_03
# avec : Scene_2012_01_11_15_10_03 : le nom du fichier obj
#
# Réalise :

import bpy 
import math
import mathutils
import sys
import os
import pprint

def pointCameraToTarget(cam):
    # targetLoc is (x, y, z) of what we want to point at
    # camera angles appear to be set up so that
    #  cam.rotation_euler = Euler((0,0,0), 'XYZ') points downward,
    #  i.e., along the -z axis direction.
    # In the xy plane (i.e., rotate around z-axis):
    dx = 0 - cam.location.x
    dy = 0 - cam.location.y
    dz = 0 - cam.location.z
    #print("dx, dy, dz:", dx, dy, dz)
    # Signs are chosen carefully due to geometry.  If we rotate
    #  by this much from the -z orientation around the x-axis, we
    #  will be pointing along the y-axis (for angle < pi rad)
    xRad = (3.14159/2.) + math.atan2(dz, math.sqrt(dy**2 + dx**2))
    #print("xRad: %f, %f deg" % (xRad, xRad*180./math.pi))

    zRad = math.atan2(dy, dx) - (3.14159256 / 2.)
    #print("zRad: %f, %f deg" % (zRad, zRad*180./math.pi))
    cam.rotation_euler = mathutils.Euler((xRad, 0, zRad), 'XYZ')

# getting mesh name from command line arguments
meshname = str(sys.argv[5])

# Suppression du cube présent par défaut
bpy.ops.object.select_by_type(type='MESH')
bpy.ops.object.delete()

# Chargement d'un fichier OBJ
bpy.ops.import_scene.obj(filepath= meshname,filter_glob="*.obj;*.mtl", axis_forward='X', axis_up='Y')

# Définition de la caméra
camera = bpy.context.scene.camera

# Définition de la lumière
lamp = bpy.data.objects['Lamp'] # bpy.types.Camera
lamp.scale = (1, 1, 1)
bpy.data.lamps[0].energy = 150

max_size = 0
s = 1

minx = 0
maxx = 0
miny = 0
maxy = 0
minz = 0
maxz = 0
for object in bpy.data.objects:
    if(object.type == 'MESH'):
        if(max(object.dimensions) > max_size):
            max_size = max(object.dimensions)

        # Defining scale
        s = 60/max_size

        for [x,y,z] in object.bound_box:
            print (x, y, z)
            if(x < minx):
                minx = x
            if(y<miny):
                miny = y
            if(z<minz):
                minz = z
            if(x > maxx):
                maxx = x
            if(y > maxy):
                maxy = y
            if(z > maxz):
                maxz = z
        width = maxx - minx
        height = maxy - miny
        depth = maxz - minz

        bpy.ops.transform.resize(value=(s,s,s))

        width *= s
        height *= s
        depth *= s

        #ensure origin is centered on bounding box center
        bpy.ops.object.origin_set(type='ORIGIN_GEOMETRY', center='BOUNDS')

        bpy.data.objects[object.name].location.x = 0
        bpy.data.objects[object.name].location.y = 0
        bpy.data.objects[object.name].location.z = 0

for material in bpy.data.materials:
    print("material = " + material.name)

camera.location.x = 80
camera.location.y = 0
camera.location.z = 0
camera.data.angle = 80*(math.pi/180.0)

lamp.location = camera.location
lamp.location.z += 20

pointCameraToTarget(camera)

# Destination d'enregistrement de l'image (l'extension png est rajoutée automatiquement)
bpy.context.scene.render.filepath = meshname

# Rendu et enregistrement de l'image dans le filepath ci-dessus
bpy.ops.render.render(write_still=True)
