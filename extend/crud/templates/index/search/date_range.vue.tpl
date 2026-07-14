                    <el-form-item label="<%label%>">
                        <el-date-picker
                            v-model="advanced.<%prop%>"
                            type="daterange"
                            value-format="YYYY-MM-DD"
                            start-placeholder="开始"
                            end-placeholder="结束"
                            class="!w-56"
                        />
                    </el-form-item>
