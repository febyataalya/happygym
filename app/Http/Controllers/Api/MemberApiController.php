package com.happygym.member.ui

import android.app.DatePickerDialog
import android.app.TimePickerDialog
import android.widget.Toast
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.happygym.member.HappyRed
import com.happygym.member.data.BookingPtData
import com.happygym.member.data.InstrukturData
import com.happygym.member.data.KetersediaanData
import com.happygym.member.data.MemberPaketPtData
import com.happygym.member.network.ApiConfig
import coil.compose.AsyncImage
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.ui.draw.clip
import androidx.compose.ui.layout.ContentScale
import kotlinx.coroutines.launch
import java.util.Calendar

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun InstrukturScreen(memberId: Int, onOpenDrawer: () -> Unit) {
    val context = LocalContext.current
    val scope = rememberCoroutineScope()
    var selectedTab by remember { mutableIntStateOf(0) }
    var refreshTrigger by remember { mutableIntStateOf(0) }
    var isLoading by remember { mutableStateOf(true) }

    // State Booking (Tab 0)
    var paketPt by remember { mutableStateOf<MemberPaketPtData?>(null) }
    var listCoachCabang by remember { mutableStateOf<List<InstrukturData>>(emptyList()) }
    var inputDate by remember { mutableStateOf("") }
    var inputTime by remember { mutableStateOf("") }
    var expanded by remember { mutableStateOf(false) }
    var selectedCoach by remember { mutableStateOf<InstrukturData?>(null) }
    var isSubmittingCoach by remember { mutableStateOf(false) }

    // State Status & Negosiasi (Tab 1)
    var listBooking by remember { mutableStateOf<List<BookingPtData>>(emptyList()) }
    var listJadwalTersedia by remember { mutableStateOf<List<KetersediaanData>>(emptyList()) }
    var showDialog by remember { mutableStateOf(false) }
    var selectedBookingToReschedule by remember { mutableStateOf<BookingPtData?>(null) }
    var inputDateDialog by remember { mutableStateOf("") }
    var inputTimeDialog by remember { mutableStateOf("") }
    var isSubmittingStatus by remember { mutableStateOf(false) }

    LaunchedEffect(memberId, refreshTrigger) {
        isLoading = true
        try {
            // 1. Load Paket PT Aktif
            val resPaket = ApiConfig.getApiService().getPaketPtAktif(memberId)
            if (resPaket.isSuccessful && resPaket.body()?.data?.isNotEmpty() == true) {
                paketPt = resPaket.body()?.data?.get(0)
            } else {
                paketPt = null
            }

            // 2. SELALU Load Daftar Coach sesuai cabang member saat ini
            // Agar saat tombol "Ganti Coach" diklik, datanya sudah siap
            val resCoach = ApiConfig.getApiService().getCoachCabang(memberId)
            if (resCoach.isSuccessful) {
                listCoachCabang = resCoach.body()?.data ?: emptyList()
            }

            // 3. Load Status Booking
            val resStatus = ApiConfig.getApiService().getRiwayatBookingPt(memberId)
            if (resStatus.isSuccessful) {
                listBooking = resStatus.body()?.data?.filter { it.status in listOf("Pending", "Negotiating", "Approved") } ?: emptyList()
            }

            // 4. Load Jadwal Tersedia Coach
            val resJadwal = ApiConfig.getApiService().getInstrukturPtTersedia(memberId)
            if (resJadwal.isSuccessful) {
                listJadwalTersedia = resJadwal.body()?.data ?: emptyList()
            }
        } catch (e: Exception) {
            Toast.makeText(context, "Gagal memuat data", Toast.LENGTH_SHORT).show()
        } finally {
            isLoading = false
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Booking Instruktur PT") },
                navigationIcon = { IconButton(onClick = onOpenDrawer) { Icon(Icons.Default.Menu, "Menu") } },
                colors = TopAppBarDefaults.topAppBarColors(containerColor = HappyRed, titleContentColor = Color.White, navigationIconContentColor = Color.White)
            )
        }
    ) { padding ->
        Column(modifier = Modifier.padding(padding).fillMaxSize().background(Color(0xFFF8F9FA))) {
            TabRow(selectedTabIndex = selectedTab, containerColor = Color.White, contentColor = HappyRed) {
                Tab(selected = selectedTab == 0, onClick = { selectedTab = 0 }, text = { Text("Pengajuan Latihan", fontWeight = if (selectedTab == 0) FontWeight.Bold else FontWeight.Normal) })
                Tab(selected = selectedTab == 1, onClick = { selectedTab = 1 }, text = { Text("Status Pengajuan", fontWeight = if (selectedTab == 1) FontWeight.Bold else FontWeight.Normal) })
            }

            if (isLoading) {
                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) { CircularProgressIndicator(color = HappyRed) }
            } else {
                Box(modifier = Modifier.fillMaxSize().padding(16.dp)) {
                    if (selectedTab == 0) {
                        // TAB PENGJUAN JADWAL
                        if (paketPt == null) {
                            Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                                    Text("Oops!", fontSize = 24.sp, fontWeight = FontWeight.Bold, color = HappyRed)
                                    Spacer(modifier = Modifier.height(8.dp))
                                    Text("Anda belum memiliki Paket PT aktif atau sisa sesi habis.", textAlign = TextAlign.Center, color = Color.Gray)
                                }
                            }
                        } else if (paketPt?.instruktur_id == null) {
                            Column(modifier = Modifier.fillMaxSize()) {
                                Text("Pilih Personal Trainer Anda", fontSize = 20.sp, fontWeight = FontWeight.Bold)
                                Spacer(modifier = Modifier.height(8.dp))
                                Text("Pilih coach yang akan mendampingi Anda. Tidak dapat diganti setelah dipilih.", fontSize = 14.sp, color = Color.Gray)
                                Spacer(modifier = Modifier.height(16.dp))
                                
                                LazyColumn(modifier = Modifier.weight(1f)) {
                                    items(listCoachCabang) { c ->
                                        val isSelected = selectedCoach == c
                                        Card(
                                            modifier = Modifier
                                                .fillMaxWidth()
                                                .padding(bottom = 12.dp)
                                                .clickable { selectedCoach = c },
                                            colors = CardDefaults.cardColors(
                                                containerColor = if (isSelected) HappyRed.copy(alpha = 0.1f) else Color.White
                                            )
                                        ) {
                                            Row(modifier = Modifier.padding(16.dp), verticalAlignment = Alignment.CenterVertically) {
                                                if (c.foto_url != null) {
                                                    AsyncImage(
                                                        model = c.foto_url,
                                                        contentDescription = "Foto Coach",
                                                        contentScale = ContentScale.Crop,
                                                        modifier = Modifier.size(70.dp).clip(CircleShape).background(Color.LightGray)
                                                    )
                                                } else {
                                                    Box(modifier = Modifier.size(70.dp).clip(CircleShape).background(Color.LightGray), contentAlignment = Alignment.Center) {
                                                        Text("No Photo", fontSize = 12.sp)
                                                    }
                                                }
                                                Spacer(modifier = Modifier.width(16.dp))
                                                Column(modifier = Modifier.weight(1f)) {
                                                    Text(
                                                        text = "Coach ${c.nama}", 
                                                        fontWeight = FontWeight.Bold, 
                                                        fontSize = 18.sp,
                                                        color = if (isSelected) HappyRed else Color.Black
                                                    )
                                                    Spacer(modifier = Modifier.height(4.dp))
                                                    Text(
                                                        text = c.spesialisasi ?: "Instruktur Umum", 
                                                        color = Color.Gray, 
                                                        fontSize = 14.sp
                                                    )
                                                }
                                                
                                                if (isSelected) {
                                                    Icon(
                                                        imageVector = Icons.Default.CheckCircle,
                                                        contentDescription = "Terpilih",
                                                        tint = HappyRed,
                                                        modifier = Modifier.size(24.dp)
                                                    )
                                                }
                                            }
                                        }
                                    }
                                }

                                Spacer(modifier = Modifier.height(16.dp))
                                Button(
                                    onClick = {
                                        if (selectedCoach == null) return@Button
                                        scope.launch {
                                            isSubmittingCoach = true
                                            try {
                                                val res = ApiConfig.getApiService().pilihCoachPt(paketPt!!.member_paket_id, selectedCoach!!.instruktur_id)
                                                if (res.isSuccessful) refreshTrigger++
                                            } catch (e: Exception) {} finally { isSubmittingCoach = false }
                                        }
                                    },
                                    modifier = Modifier.fillMaxWidth().height(50.dp),
                                    colors = ButtonDefaults.buttonColors(containerColor = HappyRed),
                                    enabled = !isSubmittingCoach && selectedCoach != null
                                ) { Text("SIMPAN COACH") }
                            }
                        } else {
                            // FORM PENGAJUAN
                            Column(modifier = Modifier.fillMaxSize().verticalScroll(rememberScrollState())) {
                                Surface(modifier = Modifier.fillMaxWidth(), color = Color(0xFFF3E5F5), shape = RoundedCornerShape(8.dp)) {
                                    Column(modifier = Modifier.padding(16.dp)) {
                                        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                                            Text("Sisa Kuota PT Anda: ${paketPt!!.sisa_sesi} Sesi", color = Color(0xFF6A1B9A), fontWeight = FontWeight.Bold)
                                            
                                            // Tombol Ganti Coach (Jika ingin pilih ulang di cabang baru)
                                            if (paketPt!!.sisa_sesi == paketPt!!.paket?.jumlah_sesi) {

                                                IconButton(
                                                    onClick = {
                                                        // boleh pilih ulang coach hanya di awal sesi
                                                        paketPt = paketPt!!.copy(instruktur_id = null)
                                                    },
                                                    modifier = Modifier.size(24.dp)
                                                ) {
                                                    Icon(
                                                        Icons.Default.Edit,
                                                        contentDescription = "Ganti Coach",
                                                        tint = Color(0xFF6A1B9A),
                                                        modifier = Modifier.size(18.dp)
                                                    )
                                                }
                                            }
                                        }
                                        Spacer(modifier = Modifier.height(12.dp))
                                        Row(verticalAlignment = Alignment.CenterVertically) {
                                            if (paketPt!!.instruktur?.foto_url != null) {
                                                AsyncImage(
                                                    model = paketPt!!.instruktur!!.foto_url,
                                                    contentDescription = "Foto Coach",
                                                    contentScale = ContentScale.Crop,
                                                    modifier = Modifier.size(50.dp).clip(CircleShape).background(Color.White)
                                                )
                                            } else {
                                                Box(modifier = Modifier.size(50.dp).clip(CircleShape).background(Color.White), contentAlignment = Alignment.Center) {
                                                    Text("No Photo", fontSize = 10.sp)
                                                }
                                            }
                                            Spacer(modifier = Modifier.width(12.dp))
                                            Column {
                                                Text("Coach: ${paketPt!!.instruktur?.nama ?: "..."}", color = Color.DarkGray, fontWeight = FontWeight.Bold)
                                                Text(paketPt!!.instruktur?.spesialisasi ?: "Instruktur Umum", color = Color.Gray, fontSize = 12.sp)
                                            }
                                        }
                                    }
                                }
                                Spacer(modifier = Modifier.height(16.dp))
                                Text("Jadwal Tersedia Coach", fontSize = 18.sp, fontWeight = FontWeight.Bold)
                                Spacer(modifier = Modifier.height(8.dp))
                                if (listJadwalTersedia.isEmpty()) {
                                    Text("Belum ada jadwal ketersediaan yang diinput coach.", color = Color.Gray, fontSize = 14.sp)
                                } else {
                                    LazyRow(
                                        horizontalArrangement = Arrangement.spacedBy(8.dp),
                                        contentPadding = PaddingValues(vertical = 4.dp)
                                    ) {
                                        items(listJadwalTersedia) { jadwal ->
                                            val isSelected = inputDate == jadwal.tanggal && inputTime == jadwal.jam_mulai
                                            FilterChip(
                                                selected = isSelected,
                                                onClick = {
                                                    inputDate = jadwal.tanggal
                                                    inputTime = jadwal.jam_mulai
                                                },
                                                label = {
                                                    Column(horizontalAlignment = Alignment.CenterHorizontally, modifier = Modifier.padding(vertical = 4.dp)) {
                                                        Text(jadwal.tanggal, fontSize = 11.sp)
                                                        Text(jadwal.jam_mulai, fontWeight = FontWeight.Bold, fontSize = 14.sp)
                                                    }
                                                },
                                                colors = FilterChipDefaults.filterChipColors(
                                                    selectedContainerColor = HappyRed,
                                                    selectedLabelColor = Color.White
                                                )
                                            )
                                        }
                                    }
                                }

                                Spacer(modifier = Modifier.height(16.dp))
                                Text("Ajukan Jadwal Latihan", fontSize = 18.sp, fontWeight = FontWeight.Bold)
                                Spacer(modifier = Modifier.height(8.dp))
                                Text("Pilih tanggal dan jam menggunakan picker di bawah ini.", fontSize = 14.sp, color = Color.Gray)
                                Spacer(modifier = Modifier.height(16.dp))

                                val cal = Calendar.getInstance()
                                OutlinedTextField(
                                    value = inputDate,
                                    onValueChange = {},
                                    readOnly = true,
                                    label = { Text("Pilih Tanggal (Ketuk)") },
                                    modifier = Modifier.fillMaxWidth().clickable {
                                        DatePickerDialog(context, { _, y, m, d -> inputDate = "$y-${String.format("%02d", m+1)}-${String.format("%02d", d)}" }, cal.get(Calendar.YEAR), cal.get(Calendar.MONTH), cal.get(Calendar.DAY_OF_MONTH)).show()
                                    },
                                    enabled = false,
                                    colors = OutlinedTextFieldDefaults.colors(disabledTextColor = Color.Black, disabledBorderColor = Color.Gray, disabledLabelColor = Color.Gray)
                                )
                                Spacer(modifier = Modifier.height(12.dp))
                                OutlinedTextField(
                                    value = inputTime,
                                    onValueChange = {},
                                    readOnly = true,
                                    label = { Text("Pilih Jam (Ketuk)") },
                                    modifier = Modifier.fillMaxWidth().clickable {
                                        TimePickerDialog(context, { _, h, m -> inputTime = "${String.format("%02d", h)}:${String.format("%02d", m)}" }, cal.get(Calendar.HOUR_OF_DAY), cal.get(Calendar.MINUTE), true).show()
                                    },
                                    enabled = false,
                                    colors = OutlinedTextFieldDefaults.colors(disabledTextColor = Color.Black, disabledBorderColor = Color.Gray, disabledLabelColor = Color.Gray)
                                )
                                Spacer(modifier = Modifier.height(24.dp))
                                Button(
                                    onClick = {
                                        if (inputDate.isEmpty() || inputTime.isEmpty()) { Toast.makeText(context, "Lengkapi!", Toast.LENGTH_SHORT).show(); return@Button }
                                        scope.launch {
                                            try {
                                                val res = ApiConfig.getApiService().bookingPt(paketPt!!.member_paket_id, inputDate, inputTime)
                                                if (res.isSuccessful) { 
                                                    Toast.makeText(context, "Pengajuan Terkirim!", Toast.LENGTH_SHORT).show() 
                                                    inputDate = ""
                                                    inputTime = ""
                                                    refreshTrigger++
                                                }
                                            } catch (e: Exception) {}
                                        }
                                    },
                                    modifier = Modifier.fillMaxWidth().height(50.dp),
                                    colors = ButtonDefaults.buttonColors(containerColor = HappyRed)
                                ) { Text("Ajukan Jadwal", fontWeight = FontWeight.Bold) }
                                Spacer(modifier = Modifier.height(50.dp))
                            }
                        }
                    } else {
                        // TAB STATUS & NEGOSIASI
                        if (listBooking.isEmpty()) {
                            Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) { Text("Belum ada status pengajuan.", color = Color.Gray) }
                        } else {
                            LazyColumn(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                                items(listBooking) { booking ->
                                    Card(modifier = Modifier.fillMaxWidth(), colors = CardDefaults.cardColors(containerColor = Color.White), elevation = CardDefaults.cardElevation(2.dp)) {
                                        Column(modifier = Modifier.padding(16.dp)) {
                                            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                                                Text("${booking.tanggal_sesi}", fontWeight = FontWeight.Bold)
                                                Surface(color = Color(0xFFE3F2FD), shape = RoundedCornerShape(4.dp)) {
                                                    Text(booking.status, modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp), fontSize = 12.sp, color = Color(0xFF1565C0))
                                                }
                                            }
                                            Text("Jam: ${booking.jam_sesi}", fontSize = 14.sp)
                                            Text("Coach: ${booking.instruktur?.nama ?: "-"}", fontSize = 14.sp, color = Color.Gray)
                                            if (booking.status == "Negotiating") {
                                                Spacer(modifier = Modifier.height(12.dp))
                                                Surface(color = Color(0xFFFFF3E0), shape = RoundedCornerShape(8.dp)) {
                                                    Column(modifier = Modifier.padding(12.dp).fillMaxWidth()) {
                                                        Text("Saran Coach:", fontWeight = FontWeight.Bold, color = Color(0xFFE65100))
                                                        Text("Alasan: ${booking.alasan_penolakan ?: "-"}", fontSize = 13.sp)
                                                        Text("Coba: ${booking.saran_tanggal} Jam ${booking.saran_jam}", fontSize = 13.sp, fontWeight = FontWeight.Bold)
                                                    }
                                                }
                                                Spacer(modifier = Modifier.height(12.dp))
                                                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                                                    Button(onClick = { scope.launch { ApiConfig.getApiService().tanggapanNegosiasiPt(booking.booking_id, "terima"); refreshTrigger++ }}, modifier = Modifier.weight(1f), colors = ButtonDefaults.buttonColors(containerColor = HappyRed)) { Text("Terima") }
                                                    OutlinedButton(onClick = { selectedBookingToReschedule = booking; showDialog = true; inputDateDialog=""; inputTimeDialog="" }, modifier = Modifier.weight(1f)) { Text("Bisa Lain Waktu?") }
                                                }
                                            } else if (booking.status == "Approved") {
                                                Spacer(modifier = Modifier.height(8.dp))
                                                OutlinedButton(onClick = { selectedBookingToReschedule = booking; showDialog = true; inputDateDialog=""; inputTimeDialog="" }, modifier = Modifier.fillMaxWidth()) { Text("Reschedule") }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // DIALOG RESCHEDULE DENGAN PICKER
        if (showDialog && selectedBookingToReschedule != null) {
            val cal = Calendar.getInstance()
            AlertDialog(
                onDismissRequest = { showDialog = false },
                title = { Text("Ajukan Ulang / Reschedule") },
                text = {
                    Column {
                        Text("Terakhir Diajukan: ${selectedBookingToReschedule?.tanggal_sesi} ${selectedBookingToReschedule?.jam_sesi}", fontSize = 13.sp, color = Color.Gray)
                        Spacer(modifier = Modifier.height(16.dp))
                        OutlinedTextField(
                            value = inputDateDialog, onValueChange = {}, readOnly = true, label = { Text("Tanggal Baru (Ketuk)") },
                            modifier = Modifier.fillMaxWidth().clickable {
                                DatePickerDialog(context, { _, y, m, d -> inputDateDialog = "$y-${String.format("%02d", m+1)}-${String.format("%02d", d)}" }, cal.get(Calendar.YEAR), cal.get(Calendar.MONTH), cal.get(Calendar.DAY_OF_MONTH)).show()
                            }, enabled = false, colors = OutlinedTextFieldDefaults.colors(disabledTextColor = Color.Black, disabledBorderColor = Color.Gray)
                        )
                        Spacer(modifier = Modifier.height(8.dp))
                        OutlinedTextField(
                            value = inputTimeDialog, onValueChange = {}, readOnly = true, label = { Text("Jam Baru (Ketuk)") },
                            modifier = Modifier.fillMaxWidth().clickable {
                                TimePickerDialog(context, { _, h, m -> inputTimeDialog = "${String.format("%02d", h)}:${String.format("%02d", m)}" }, cal.get(Calendar.HOUR_OF_DAY), cal.get(Calendar.MINUTE), true).show()
                            }, enabled = false, colors = OutlinedTextFieldDefaults.colors(disabledTextColor = Color.Black, disabledBorderColor = Color.Gray)
                        )
                    }
                },
                confirmButton = {
                    Button(onClick = {
                        if (inputDateDialog.isEmpty() || inputTimeDialog.isEmpty()) return@Button
                        scope.launch {
                            isSubmittingStatus = true
                            try {
                                val res = ApiConfig.getApiService().tanggapanNegosiasiPt(selectedBookingToReschedule!!.booking_id, "reschedule", inputDateDialog, inputTimeDialog)
                                if (res.isSuccessful) { showDialog = false; refreshTrigger++ }
                            } catch (e: Exception) {} finally { isSubmittingStatus = false }
                        }
                    }, colors = ButtonDefaults.buttonColors(containerColor = HappyRed)) { Text("Ajukan") }
                },
                dismissButton = { TextButton(onClick = { showDialog = false }) { Text("Batal") } }
            )
        }
    }
}