<!-- UTF8, 27.02.2019 -->
<div class="tabbable-custom nav-justified">
	<ul class="nav nav-tabs nav-justified">
		<li class="active"><a href="#tab_0" data-toggle="tab" aria-expanded="true"> Giriş Bilgileri </a></li>
		<li><a href="#tab_1" data-toggle="tab" aria-expanded="true"> Yetkili Bilgileri </a></li>
		<li><a href="#tab_2" data-toggle="tab" aria-expanded="true"> Müşteri Bilgileri </a></li>
		<li><a href="#tab_3" data-toggle="tab" aria-expanded="true"> Bağlantılı Firmalar </a></li>
	</ul>
	<div class="tab-content">
		<div class="tab-pane active" id="tab_0">
			<table class="ds_table">
			<tbody class="ds_tbody">
			<tr class="ds_tr ds_tr_1">
				<td class="ds_td ds_td_1" style="width: 50%;">
					<label class="ds_form_label">Kullanıcı Adı (*)</label><br>
					{acc_name}
				</td>
				<td class="ds_td ds_td_1" style="width: 50%;">
					<label class="ds_form_label">Parola</label><br>
					{acc_pass}
				</td>
			</tr>
			<tr class="ds_tr ds_tr_2">
				<td class="ds_td ds_td_1">
					<label class="ds_form_label">Adı, Soyadı (*)</label><br>
					{name_surname}
				</td>
				<td class="ds_td ds_td_1">
					<label class="ds_form_label">Parola Yenileme Tarihini Resetle</label><br>
					<input type="checkbox" name="a--acc_pass_reset" value="1"> Bir sonra ki girişte parola yenileme iste.
				</td>
			</tr>
			<tr class="ds_tr ds_tr_1">
				<td class="ds_td ds_td_1">
					<label class="ds_form_label">Durum (*)</label><br>
					{status}
				</td>
				<td class="ds_td ds_td_1">
					<label class="ds_form_label">Fotoğraf</label><br>
					{imgurl}
				</td>
			</tr>
			</tbody>
			</table>
		</div>
		<div class="tab-pane" id="tab_1">
			<table class="ds_table">
			<tbody class="ds_tbody">
			<tr class="ds_tr ds_tr_1">
				<td class="ds_td ds_td_1" style="width: 50%;">
					<label class="ds_form_label">Departman (*)</label><br>
					{dep_id}
				</td>
				<td class="ds_td ds_td_1" style="width: 50%;">
					<label class="ds_form_label">Yetki (*)</label><br>
					{user_level}
				</td>
			</tr>
			</tbody>
			</table>
		</div>
		<div class="tab-pane" id="tab_2">
			<table class="ds_table">
			<tbody class="ds_tbody">
			<tr class="ds_tr ds_tr_1">
				<td class="ds_td ds_td_1" style="width: 50%;">
					<label class="ds_form_label">Müşteri</label><br>
					{cus_id}
				</td>
				<td class="ds_td ds_td_1" style="width: 50%;">
					<label class="ds_form_label">Müşteri Departmanı</label><br>
					{cus_dep_id}
				</td>
			</tr>
			</tbody>
			</table>
		</div>
		<div class="tab-pane" id="tab_3">
			<table class="ds_table">
			<tbody class="ds_tbody">
			<tr class="ds_tr ds_tr_1">
				<td class="ds_td ds_td_1" style="width: 40%;">
					<select name="z--firm_list" id="z--firm_list" class="form-control select2-ajax select2-ajax-firm_list" data-ajaxindex="firm_list" 
						data-ajaxsql="{p_firm_sql}" data-placeholder="Bağlantılı Firma Seçiniz..." data-minchar="1" style="width: 100%;">
					</select>
				</td>
				<td class="ds_td ds_td_2" style="width: 40%;">
					<select name="z--firm_type" id="z--firm_type" class="form-control select2" data-placeholder="Firma Tipi Seçiniz..." style="width: 100%;">
						<option value=""></option>
						<option value="1">Bayi</option>
						<option value="2">İthalatçı</option>
					</select>
				</td>
				<td class="ds_td ds_td_3" style="width: 20%;">
					<a href="javascript:;" class="btn red" onclick="yonetim_accounts_firma_ekle();"> <i class="fa fa-edit" aria-hidden="true"></i> Ekle</a>
				</td>
			</tr>
			</tbody>
			</table><br>
		
			{p_datatable_output}
		</div>
	</div>
</div>