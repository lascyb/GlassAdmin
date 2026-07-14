                    <el-form-item label="<%label%>">
                        <div class="flex items-center gap-1">
                            <el-input-number v-model="advanced.<%prop%>_min" controls-position="right" class="!w-28" placeholder="最小" />
                            <span class="text-gray-400">~</span>
                            <el-input-number v-model="advanced.<%prop%>_max" controls-position="right" class="!w-28" placeholder="最大" />
                        </div>
                    </el-form-item>