<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;
use dosamigos\ckeditor\CKEditor;

/** @var yii\web\View $this */
/** @var common\models\Product $model */
/** @var yii\widgets\ActiveForm $form */
?>
<link href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/themes/smoothness/jquery-ui.min.css" rel="stylesheet" />
<script src="<?= Yii::$app->request->baseUrl ?>/js/jquery-3.2.1.min.js"></script>
<script src="<?= Yii::$app->request->baseUrl ?>/js/jquery-ui.js"></script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;500&display=swap');

    .note {
        font-family: cursive;
        text-transform: none;
        font-size: 18px;
        border: 1px solid yellow;
    }
</style>
<div class="product-form">
    <?php $form = ActiveForm::begin(); ?>
    <div class="row">
        <div class="col-lg-3 col-md-4">

            <?= $form->field($model, 'template_id')->dropDownList($template_name, ['prompt' => 'Select Template Name', 'required' => true])->label(false) ?>

            <?= $form->field($model, 'language_id')->dropDownList($language_name, ['prompt' => 'Select language', 'required' => true, 'onchange' => 'get_images(this.value)'])->label(false) ?>

        </div>
        <div class="col-lg-9">

            <div class="row">
                <div class="col-md-9 pos">
                    <div style="height:100%; width:100%; overflow:scroll">
                        <div class="xyz" style=" height: 1000px; width:1000px;display:none;">
                            <div class="note-placeholder"></div>
                            <div class="overlay-placeholder"></div>
                        </div>
                        <div class="xyz1" style=" height: 1000px; width:800px;display:none;">
                            <div class="note-placeholder"></div>
                            <div class="overlay-placeholder"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div>
                        <h1>Text</h1>
                        <div class="row">
                            <div class="col-md-6">
                                <input type="text" name="item" class="item slt" id="item" placeholder="Enter item name">
                            </div>
                            <div class="col-md-6 bt">
                                <button type="button" class="btn btn-warning" id="add">Add Item</button>

                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <select id="textalign" name="textalign" class="slt" onchange="changeNoteTextalign(this.value)">
                                    <option value="" selected>Select Text Align</option>
                                    <option value="center">center</option>
                                    <option value="start">start</option>
                                    <option value="end">end</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <select id="fontstyle" name="fontstyle" class="slt" onchange="changeNoteFontstyle(this.value)">
                                    <option value="" selected>Select Font Style</option>
                                    <option value="italic">italic</option>
                                    <option value="normal">normal</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <!-- <select id="fontcolor" name="fontcolor" class="slt" onchange="changeNoteColor(this.value)">
                                    <option value="" selected>Select Font Color</option>
                                    <option value="white">white</option>
                                    <option value="black">black</option>
                                    <option value="red">red</option>
                                </select>-->
                                <label for="colorPicker">Select a color:</label>
                                <input type="color" id="fontcolor" name="fontcolor" value="#ff0000" class="slt" onchange="changeNoteColor(this.value)">
                            </div>
                            <div class="col-md-6">
                                <select id="fontweight" name="fontweight" class="slt" onchange="changeNoteFontweight(this.value)">
                                    <option value="" selected>Select font weight</option>
                                    <option value="bold">bold</option>
                                    <option value="normal">normal</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <input type="text" name="posX[]" id="posX" class="slt" placeholder="X position" />
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="posY[]" id="posY" class="slt" placeholder="Y position" />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <input type="number" id="fontsize" class="slt" placeholder="select Font Size" min="18" max="200" onchange="changeNoteFontsize(this.value)">
                            </div>
                            <div class="col-md-6">
                                <select id="fontfamily" name="fontfamily" class="slt" onchange="changeNoteFontfamily(this.value)">
                                    <option value="" selected>Select font family</option>
                                    <option value="cursive">cursive</option>
                                    <option value="emoji">emoji</option>
                                    <option value="fantasy">fantasy</option>
                                    <option value="inherit">inherit</option>
                                    <option value="initial">initial</option>
                                    <option value="math">math</option>
                                    <option value="monospace">monospace</option>
                                    <option value="none">none</option>
                                    <option value="system-ui">system-ui</option>
                                    <option value="Arial, sans-serif">Arial</option>
                                    <option value="Verdana, sans-serif">Verdana</option>
                                    <option value="Tahoma, sans-serif">Tahoma</option>
                                    <option value="'Trebuchet MS', sans-serif">Trebuchet MS</option>
                                    <option value="'Lucida Sans', sans-serif">Lucida Sans</option>
                                    <option value="Georgia, serif">Georgia</option>
                                    <option value="'Times New Roman', serif">Times New Roman</option>
                                    <option value="Garamond, serif">Garamond</option>
                                    <option value="'Courier New', monospace">Courier New</option>
                                    <option value="Impact, fantasy">Impact</option>
                                    <option value="'Comic Sans MS', cursive">Comic Sans MS</option>
                                    <option value="'Brush Script MT', cursive">Brush Script MT</option>
                                    <option value="'Dancing Script', cursive">Dancing Script</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <input type="text" name="textwidth" id="textwidth" class="slt" placeholder="Text Width" />
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="textheight" id="textheight" class="slt" placeholder="Text Height" />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <input type="hidden" name="textid" class="textid" id="textid">
                            </div>
                            <div class="col-md-6">
                                <input type="hidden" name="itemary[]">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <button type="button" class="btn btn-primary" id="saveText" onclick="get_items()">Save Text</button>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h1>Image</h1>
                        <div class="row">
                            <div class="col-md-6">
                                <select id="imagePlace" name="imagePlace" class="slt">
                                    <option value="" selected>Select Image place</option>
                                    <option value="bg">BG</option>
                                    <option value="fg">FG</option>
                                </select>
                            </div>
                            <div class="col-md-6 bt">
                                <button type="button" class="btn btn-warning" id="addImg">Add Image</button>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <input type="text" name="posLeft[]" id="posLeft" class="slt" placeholder="X position" />
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="posTop[]" id="posTop" class="slt" placeholder="Y position" />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <input type="text" name="height[]" id="height" class="slt" placeholder="height" value="100" />
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="width[]" id="width" class="slt" placeholder="width" value="100" />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <input type="number" id="rotate" class="slt" placeholder="Select Rotate Degree" max="360" onchange="rotateOverlay(this.value)">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <button type="button" class="btn btn-primary" id="saveImg" onclick="get_img()">Save image</button>
                            </div>
                            <div class="col-md-6">
                                <input type="hidden" name="imgid" class="imgid" id="imgid">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="appenddiv"></div>
            </div>


        </div>
    </div>
</div>

<div class="form-group">
    <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
</div>
<?php ActiveForm::end(); ?>
</div>

<script>
    function get_images(language_id) {
        var csrfToken = $('meta[name="csrf-token"]').attr("content");
        var template_id = $('#product-template_id').val();
        var path = '<?= Yii::getAlias('@storageUrl') ?>/images/';
        $.ajax({
            url: "<?= Url::toRoute(['product/images']) ?>",
            method: "POST",
            data: {
                language_id: language_id,
                template_id: template_id,
                '_csrf-backend': csrfToken
            },
            success: function(result) {
                if (result != '') {
                    var imgtype = JSON.parse(result);
                     var img = path + imgtype.img;
                    if (imgtype.image_type == 'square') {
                        $('.xyz').attr('style', 'background-image: url(' + img + '); display: block;height: 1000px; width:1000px;');
                        $('.xyz1').css("display", "none");
                    } else {
                        $('.xyz1').attr('style', 'background-image: url(' + img + '); display: block;height: 1000px; width:800px;');
                        $('.xyz').css("display", "none");

                    }
                }
            }
        });
    }
</script>





<script>
    var appendedList = [];

    function makeEditorId(prefix) {
        return prefix + '-' + Date.now() + '-' + Math.floor(Math.random() * 100000);
    }

    function getElementByStoredId(inputSelector) {
        var id = $(inputSelector).val();
        if (!id) {
            return $();
        }

        return $(document.getElementById(id));
    }

    function syncSelectedTextFields($note) {
        if (!$note.length) {
            return;
        }

        $('#item').val($note.text());
        $('#textid').val($note.attr('id'));
        $('#posX').val(Math.round(parseFloat($note.css('left')) || 0));
        $('#posY').val(Math.round(parseFloat($note.css('top')) || 0));
        $('#textwidth').val(Math.round($note.outerWidth()));
        $('#textheight').val(Math.round($note.outerHeight()));
    }

    function get_items() {
        var template_id = $('#product-template_id').val();
        var language_id = $('#product-language_id').val();
        var csrfToken = $('meta[name="csrf-token"]').attr("content");

        var min = 10000;
        var max = 99999;
        var num = Math.floor(Math.random() * (max - min + 1)) + min;

        var id = num;
        var type = 'text';
        var itemName = $('#item').val();
        var itemName1 = itemName.trim();
        var posx = $('#posX').val();
        var posy = $('#posY').val();
        var textalign = $('#textalign').val();
        var color = $('#fontcolor').val();
        var fontstyle = $('#fontstyle').val();
        var fontweight = $('#fontweight').val();
        var fontsize = $('#fontsize').val();
        var fontfamily = $('#fontfamily').val();
        var width = $('#textwidth').val();
        var height = $('#textheight').val();

        var Id = $('#textid').val();
        if (!Id) {
            alert('please select a text');
            return;
        }
        var Id1 = Id;
        //console.log(Id);

        var existingItem = appendedList.find(function(item) {
            return item.id === Id1;
        });

        if (existingItem) {
            existingItem.x = posx;
            existingItem.y = posy;
            existingItem.text = itemName1;
            existingItem.textalign = textalign;
            existingItem.fontstyle = fontstyle;
            existingItem.color = color;
            existingItem.fontweight = fontweight;
            existingItem.fontsize = fontsize;
            existingItem.fontfamily = fontfamily;
            existingItem.width = width;
            existingItem.height = height;
            console.log("Item exists in updateList");
        } else {
            var newItem = {
                id: Id1,
                type: type,
                x: posx,
                y: posy,
                text: itemName.trim(),
                place: '',
                color: color,
                textalign: textalign,
                fontstyle: fontstyle,
                fontweight: fontweight,
                fontsize: fontsize,
                fontfamily: fontfamily,
                width: width,
                height: height,


            };
            console.log("Item does not exist in updateList");
            appendedList.push(newItem);
        }
        console.log(newItem);
        console.log(appendedList);



        $.ajax({
            url: "<?= Url::toRoute(['product/items']) ?>",
            method: "POST",
            data: {
                appendedList: JSON.stringify(appendedList),
                language_id: language_id,
                template_id: template_id,
                '_csrf-backend': csrfToken
            },
            success: function(result) {
                if (result == 0) {

                    var tname = $('input[name=item]').val();

                    var tid = $('input[name=textid]').val();
                    if (tid !== '') {
                        $(document.getElementById(tid)).text(tname);
                    }
                    if (Id1) {
                        alert('save successful');
                    } else {
                        alert('please select a text');
                    }
                    $('input[name=item]').val('');
                    $('input[name=textid]').val('');
                    $('#posX').val('');
                    $('#posY').val('');
                    $('#textalign').val('');
                    $('#fontweight').val('');
                    $('#fontcolor').val('');
                    $('#fontstyle').val('');
                    $('#fontsize').val('');
                    $('#fontfamily').val('');
                    $('#textwidth').val('');
                    $('#textheight').val('');
                } else {
                    alert('Error');
                }
            }
        });
    }

    $(document).ready(function() {
        var $item = null;
        var $currentDraggedItem = null;
        $('#add').click(function() {
            var template_id = $('#product-template_id').val();
            var csrfToken = $('meta[name="csrf-token"]').attr("content");
            var toAdd = $.trim($('input[name=item]').val());
            if (!toAdd) {
                alert('Please enter text first');
                return;
            }
            $.ajax({
                url: "<?= Url::toRoute(['product/imagetype']) ?>",
                method: "POST",
                data: {
                    template_id: template_id,
                    '_csrf-backend': csrfToken
                },
                success: function(result) {
                    if (result) {
                        var uId = makeEditorId('txt');
                        $item = $('<div class="note"/>').text(toAdd);
                        $item.attr('id', uId);
                        $('#textid').val(uId);


                        $item.draggable({
                            cursor: "crosshair",
                            start: function(e, ui) {
                                // Set the currently dragged item
                                $currentDraggedItem = $(this);
                            },
                            stop: function(e, ui) {
                                $currentDraggedItem = null;
                            },
                            drag: function(e, ui) {


                                var template_id = $('#product-template_id').val();
                                var language_id = $('#product-language_id').val();
                                var text_id = $('#textid').val();
                                var csrfToken = $('meta[name="csrf-token"]').attr("content");
                                $.ajax({
                                    url: "<?= Url::toRoute(['product/autotextfetch']) ?>",
                                    method: "POST",
                                    data: {
                                        template_id: template_id,
                                        language_id: language_id,
                                        text_id: text_id,
                                        '_csrf-backend': csrfToken
                                    },
                                    success: function(result) {
                                        if (result != '') {
                                            var textDetails = JSON.parse(result);
                                            // console.log(textDetails);
                                            $.each(textDetails, function(i, item) {
                                                // alert(item.id);
                                                if (item.id == text_id) {
                                                    $('#fontsize').val(item.fontsize);
                                                    $('#textalign').val(item.textalign);
                                                    $('#fontstyle').val(item.fontstyle);
                                                    $('#fontcolor').val(item.color);
                                                    $('#fontweight').val(item.fontweight);
                                                    $('#item').val(item.text);
                                                    $('#fontfamily').val(item.fontfamily);
                                                    $('#textwidth').val(item.width);
                                                    $('#textheight').val(item.height);

                                                }
                                            });
                                        }

                                    }
                                });

                                var itemName = $(this).text();
                                var textId = $(this).attr('id');

                                var posx = Math.round(ui.position.left);
                                var posy = Math.round(ui.position.top);
                                // populateTextBoxes(itemName);
                                $('#posX').val(posx);
                                $('#posY').val(posy);
                                $('#item').val(itemName);
                                $('#textid').val(textId);
                            }
                        });

                        $item.css({
                            'position': 'absolute',
                            'left': '0px',
                            'top': '0px',
                            'border': '1px solid yellow',
                        });

                        if (result == 'square') {
                            $item.appendTo('.xyz');
                        } else {
                            $item.appendTo('.xyz1');
                        }
                        syncSelectedTextFields($item);

                    } else {
                        alert('Error');
                    }
                }
            });
        });

        $(document).on('click', '.note', function() {
            syncSelectedTextFields($(this));
        });

        $(document).on("drag", '.note', function(e, ui) {
            if ($currentDraggedItem) {
                var textWidth = $currentDraggedItem.outerWidth();
                var textheight = $currentDraggedItem.outerHeight();
                $('#textwidth').val(textWidth);
                $('#textheight').val(textheight);
            }
        });
        $(document).on("mousedown", '.note', function() {
            $currentDraggedItem = $(this);
        });

        $(document).on("mousemove", function(e) {
            if ($currentDraggedItem) {
                var textWidth = $currentDraggedItem.outerWidth();
                var textheight = $currentDraggedItem.outerHeight();
                $('#textwidth').val(textWidth);
                $('#textheight').val(textheight);
            }
        });

        $(document).on("mouseup", function() {
            $currentDraggedItem = null;
        });
    });

    function changeNoteColor(selectedColor) {
        var noteid = getElementByStoredId('#textid');
        noteid.css('color', selectedColor);
    }

    function changeNoteFontstyle(selectedFontstyle) {
        var noteid = getElementByStoredId('#textid');
        noteid.css('font-style', selectedFontstyle);
    }

    function changeNoteFontweight(selectedFontweight) {
        var noteid = getElementByStoredId('#textid');
        noteid.css('font-weight', selectedFontweight);
    }

    function changeNoteTextalign(selectedTextalign) {
        var noteid = getElementByStoredId('#textid');
        noteid.css('text-align', selectedTextalign);
    }

    function changeNoteFontsize(selectedFontsize) {
        var noteid = getElementByStoredId('#textid');
        noteid.css('font-size', selectedFontsize + 'px');
    }

    function changeNoteFontfamily(selectedFontfamily) {
        var noteid = getElementByStoredId('#textid');
        noteid.css('font-family', selectedFontfamily);
    }
    $('#textwidth').on('input', function() {
        var newWidth = $(this).val();
        var noteid = getElementByStoredId('#textid');
        noteid.css('width', newWidth + 'px');
    });

    $('#textheight').on('input', function() {
        var newheight = $(this).val();
        var noteid = getElementByStoredId('#textid');
        noteid.css('height', newheight + 'px');
    });
</script>


<!-- Image place -->

<script>
    var appendedImage = [];

    function get_img() {
        var template_id = $('#product-template_id').val();
        var language_id = $('#product-language_id').val();
        var csrfToken = $('meta[name="csrf-token"]').attr("content");

        var min = 10000;
        var max = 99999;
        var num = Math.floor(Math.random() * (max - min + 1)) + min;


        var id = num;
        var type = 'image';
        var posLeft = $('#posLeft').val();
        var posTop = $('#posTop').val();
        var itemName = 'image';
        var imagePlace = $('#imagePlace').val();
        var height = $('#height').val();
        var width = $('#width').val();
        var rotate = $('#rotate').val();


        var Id = $('#imgid').val();
        if (!Id) {
            alert('Please Select a image');
            return;
        }
        var Id1 = Id;
        console.log(Id);
        console.log(Id1);

        var existingImage = appendedImage.find(function(image) {
            return image.id === Id1;
        });

        if (existingImage) {
            existingImage.x = posLeft;
            existingImage.y = posTop;
            existingImage.place = imagePlace;
            existingImage.height = height;
            existingImage.width = width;
            existingImage.rotate = rotate;
            console.log("Item exists in updateList");
        } else {
            var newImage = {
                id: Id1,
                type: type,
                x: posLeft,
                y: posTop,
                text: itemName,
                place: imagePlace,
                height: height,
                width: width,
                rotate: rotate,

            };
            console.log("Item does not exist in updateList");
            appendedImage.push(newImage);

        }
        console.log(newImage);
        console.log(appendedImage);
        // populateTextBoxes(imagePlace);

        $.ajax({
            url: "<?= Url::toRoute(['product/imageplace']) ?>",
            method: "POST",
            data: {
                appendedImage: JSON.stringify(appendedImage),
                language_id: language_id,
                template_id: template_id,
                '_csrf-backend': csrfToken
            },
            success: function(result) {
                if (result == 0) {
                    var tname = $('input[name=imagePlace]').val();
                    var tid = $('input[name=imgid]').val();
                    if (tid !== '') {
                        $(document.getElementById(tid)).text(tname);
                    }

                    if (tid) {
                        alert('Image Place Updated Successfully');
                    } else {
                        alert('Please Select a image');
                    }

                    $('#imagePlace').val('');
                    $('input[name=imgid]').val('');
                    $('#posLeft').val('');
                    $('#posTop').val('');
                    $('#height').val('')
                    $('#width').val('');
                    $('#rotate').val('');
                } else {
                    alert('Error');
                }
            }
        });
    }



    $(document).ready(function() {
        var $itemImg = null;

        $('#addImg').click(function() {

            var template_id = $('#product-template_id').val();
            var csrfToken = $('meta[name="csrf-token"]').attr("content");
            var posLeft = parseInt($('#posLeft').val(), 10) || 0;
            var posTop = parseInt($('#posTop').val(), 10) || 0;
            var height = parseInt($('#height').val(), 10) || 100;
            var width = parseInt($('#width').val(), 10) || 100;
            var rotate = parseInt($('#rotate').val(), 10) || 0;
            $.ajax({
                url: "<?= Url::toRoute(['product/imagetype']) ?>",
                method: "POST",
                data: {
                    template_id: template_id,
                    '_csrf-backend': csrfToken
                },
                success: function(result) {
                    if (result) {
                        $itemImg = $('<div class="overlay"/>');
                        var uId = makeEditorId('img');
                        $itemImg.attr('id', uId);
                        $('#imgid').val(uId);

                        $itemImg.css({
                            'position': 'absolute',
                            'left': posLeft + 'px',
                            'top': posTop + 'px',
                            'width': width + 'px',
                            'height': height + 'px',
                            'background-color': 'rgba(0, 0, 0, 0.5)',
                            'border': '2px solid white',
                            'transform': 'rotate(' + rotate + 'deg)',
                        });

                        $itemImg.draggable({
                            cursor: "crosshair",
                            drag: function(e, ui) {


                                var template_id = $('#product-template_id').val();
                                var language_id = $('#product-language_id').val();
                                var img_id = $('#imgid').val();
                                var csrfToken = $('meta[name="csrf-token"]').attr("content");
                                $.ajax({
                                    url: "<?= Url::toRoute(['product/autofetchimage']) ?>",
                                    method: "POST",
                                    data: {
                                        template_id: template_id,
                                        language_id: language_id,
                                        img_id: img_id,
                                        '_csrf-backend': csrfToken
                                    },
                                    success: function(result) {
                                        if (result != '') {
                                            var imgDetails = JSON.parse(result);
                                            // console.log(imgDetails);
                                            $.each(imgDetails, function(i, item) {
                                                if (item.id == img_id) {
                                                    $('#height').val(item.height);
                                                    $('#width').val(item.width);
                                                    $('#rotate').val(item.rotate);
                                                    $('#imagePlace').val(item.place);
                                                }
                                            });
                                        }

                                    }
                                });

                                var imgId = $(this).attr('id');

                                var posLeft = Math.floor(ui.position.left);
                                var posTop = Math.floor(ui.position.top);
                                var selectedval = $('#imagePlace').val();

                                // populateTextBoxes(imagePlace);
                                $('#posLeft').val(posLeft);
                                $('#posTop').val(posTop);
                                $('#imagePlace').val(selectedval);
                                $('#imgid').val(imgId);
                            }
                        }).resizable({
                            handles: 'se',
                            resize: function(event, ui) {
                                $('#height').val(ui.size.height);
                                $('#width').val(ui.size.width);
                            }
                        });
                        if (result == 'square') {
                            $('.xyz').append($itemImg);
                        } else {
                            $('.xyz1').append($itemImg);
                        }

                    } else {
                        alert('Error');
                    }
                }
            });
        });

        $(document).on('click', '.overlay', function() {
            var imgId = $(this).attr('id');
            $('#imgid').val(imgId);
            $('#posLeft').val(Math.floor(parseFloat($(this).css('left')) || 0));
            $('#posTop').val(Math.floor(parseFloat($(this).css('top')) || 0));
            $('#height').val(Math.round($(this).outerHeight()));
            $('#width').val(Math.round($(this).outerWidth()));
        });
    });

    function rotateOverlay(rotationAngle) {
        var overlayElement = getElementByStoredId('#imgid');
        overlayElement.css('transform', 'rotate(' + rotationAngle + 'deg)');
    }
</script>


<!-- End image Place -->
